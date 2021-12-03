<?php /** @noinspection PhpUndefinedMethodInspection */

namespace App\Repositories;

use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use Throwable;

/**
 * Classe base de consultas e iterações no repositório
 */
abstract class BaseRepository
{

    /**
     * @var Model
     */
    protected Model $model;

    /**
     * @var Application
     */
    protected Application $app;

    /**
     * @var \Illuminate\Database\Eloquent\Builder|Model
     */
    protected \Illuminate\Database\Eloquent\Builder|Model $baseQuery;

    /**
     * @param Application $app
     *
     * @throws \Exception
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->makeModel();
        $this->baseQuery = $this->newBaseQuery();
    }

    /**
     * Get searchable fields array
     *
     * @return array
     */
    abstract public function getFieldsSearchable();

    /**
     * Configure the Model
     *
     * @return string
     */
    abstract public function model();

    public function newBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->model->newQuery();

        if ($this->model->hasCompanyId()) {
            $query->where(
                $this->model->getTable() . '.' . 'company_id',
                auth('api')->user()->company_id ?? 1
            );
        }

        return $query;
    }

    /**
     * Make Model instance
     *
     * @throws \Exception
     *
     * @return Model
     */
    public function makeModel()
    {
        $baseModel = $this->app->make($this->model());

        if (!$baseModel instanceof Model) {
            throw new \Exception('Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model');
        }

        $this->model = $baseModel;
        return $this->model;
    }

    /**
     * Paginate records for scaffold.
     *
     * @param int $perPage
     * @param array $columns
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $perPage, array $columns = ['*'])
    {
        $query = $this->allQuery();

        return $query->paginate($perPage, $columns);
    }

    /**
     * Build a query for retrieving all records.
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function allQuery(array $search = [], ?int $skip = null, ?int $limit = null)
    {
        $query = $this->newBaseQuery();

        if (count($search)) {
            foreach ($search as $key => $value) {
                if (in_array($key, $this->getFieldsSearchable())) {
                    $query->where($key, $value);
                }
            }
        }

        if (!is_null($skip)) {
            $query->skip($skip);
        }

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Retrieve all records with given filter criteria
     *
     * @param array $search
     * @param int|null $skip
     * @param int|null $limit
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function all(array $search = [], ?int $skip = null, ?int $limit = null, array $columns = ['*'])
    {
        $query = $this->allQuery($search, $skip, $limit);

        return $query->get($columns);
    }

    /**
     * Create model record
     *
     * @param array $input
     *
     * @return Model|null
     */
    public function create(array $input)
    {
        $baseModel = $this->model->newInstance($input);

        $baseModel->save();

        return $baseModel;
    }

    /**
     * Find model record for given id
     *
     * @param int|null $id
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model|null
     */
    public function find(?int $id, array $columns = ['*'])
    {
        $query = $this->newBaseQuery();

        return $query->find($id, $columns);
    }

    /**
     * Update model record for given id
     *
     * @param array $input
     * @param int $id
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Model
     */
    public function update(array $input, int $id)
    {
        $query = $this->newBaseQuery();

        $baseModel = $query->find($id);

        $baseModel->fill($input);

        $baseModel->save();

        return $baseModel;
    }

    /**
     * @param int $id
     *
     * @throws \Exception
     *
     * @return bool|mixed|null
     */
    public function delete(int $id)
    {
        $query = $this->newBaseQuery();

        $baseModel = $query->findOrFail($id);

        return $baseModel->delete();
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @noinspection PhpUndefinedMethodInspection*@throws \Exception
     */
    #[ArrayShape([
        'code' => 'int',
        'message' => 'string'
    ])] public function deleteOrUndelete(int $id)
    {
        $query = $this->newBaseQuery()->withTrashed();

        $baseModel = $query->find($id);

        if (is_null($baseModel)) {
            return [
                'code' => 404,
                'message' => __("messages.{$this->getModelName()}") . ' não encontrado(a)'
            ];
        }

        $note = request()->get('note');
        if ($note) {
            $baseModel->note = $note;
            $baseModel->save();
            $baseModel->fresh();
        }

        if (!is_null($baseModel->deleted_at)) {
            $baseModel->restore();
            return [
                'code' => 200,
                'message' => __("messages.{$this->getModelName()}") . ' reativado(a) com sucesso'
            ];
        }

        $baseModel->delete();
        return [
            'code' => 200,
            'message' => __("messages.{$this->getModelName()}") . ' desativado(a) com sucesso'
        ];
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param array|string|\Closure $column
     * @param mixed $operator
     * @param mixed $value
     * @param string $boolean
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function findBy(array|string|\Closure $column, mixed $value, mixed $operator = '=', string $boolean = 'and')
    {
        $query = $this->newBaseQuery();

        return $query->where($column, $operator, $value, $boolean);
    }

    /**
     * Busca todos os models do sistema que estão na pasta Models
     * @return array
     */
    public function getModels()
    {
        $out = [];
        $outNames = [];
        $path = app_path() . '/Models';
        $results = scandir($path);
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') {
                continue;
            }
            $filename = $path . '/' . $result;
            if (is_dir($filename)) {
                continue;
            }
            $out[] = substr($filename, 0, -4);
        }
        foreach ($out as $value) {
            if (!str_contains($value, 'BaseModel')) {
                $outNames[] = [
                    'value' => Str::snake(Str::plural((array_reverse(explode('/', $value))[0]))),
                    'text' => trans_choice(
                        'messages.' . array_reverse(explode('/', $value))[0],
                        0
                    )
                ];
            }
        }
        return $outNames;
    }

    public function getModelName()
    {
        return Str::singular(Str::studly($this->model->getTable()));
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|Model
     * @noinspection PhpUndefinedMethodInspection
     */
    public function findAllFieldsAnd(Request $request)
    {
        $this->verifyLimiter();

        $this->verifyActive($request);

        $this->mountFieldsToSelect($request);

        $this->mountSelectToDates($request);

        $this->getWherehas($request, 'AND');

        $this->getWhereModel($request);

        $this->mountOrderBy($request);
        return $this->baseQuery->orderBy($this->model->getTable() . '.' . 'deleted_at');
    }

    /**
     * Função responsável por montar o where para a tabela principal
     * @param Request $request
     */
    public function getWhereModel(Request $request)
    {
        $inputs = $request->all();

        foreach ($inputs as $key => $value) {
            if (!in_array(Str::camel($key), $this->model->getRelationsBySearch())) {
                $type = $this->model()::getFieldType($key);
                if ($type) {
                    if ($type == 'string') {
                        $this->baseQuery->where(
                            $this->model->getTable()
                            . '.' .
                            $key,
                            'like',
                            '%' . $value . '%'
                        );
                    } else {
                        if (count(explode('-', $value, 2)) > 1 && !strtotime($value)) {
                            $this->baseQuery->whereBetween(
                                $key,
                                [
                                    explode(':', $value, 2)[0],
                                    explode(':', $value, 2)[1]
                                ]
                            );
                        } else {
                            $this->baseQuery->where(
                                $this->model->getTable()
                                . '.' . $key,
                                $inputs['operator'][$key] ?? '=',
                                $value
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Faz um join com a tabela informada no orderBy
     * e insere os campos na ordenação conforme vem no payload
     * @param Request $request
     */
    public function mountOrderBy(Request $request)
    {
        $table = $this->model->getTable();
        if ($request->exists('order')) {
            $orders = explode(',', $request->input('order'));
            $directions = [];
            if ($request->exists('direction')) {
                $directions = explode(',', $request->input('direction'));
            }
            $i = 0;
            foreach ($orders as $order) {
                if (str_contains($order, '.')) {
                    $ord = explode('.', $order);
                    if (in_array(Str::camel($ord[0]), $this->model->getRelationsBySearch())) {
                        $tableRelated = $this->model->{Str::camel($ord[0])}()->getRelated()->getTable();
                        $fieldRelated = Str::singular(Str::snake($ord[0]));
                        $this->baseQuery->select("$table.*")
                            ->leftJoin(
                                $tableRelated,
                                "$tableRelated.id",
                                "$table.{$fieldRelated}_id"
                            )
                            ->orderBy("$tableRelated.$ord[1]", $directions[$i] ?? 'asc');
                    }
                } else {
                    if (in_array($order, $this->fieldSearchable)) {
                        $this->baseQuery->orderBy($order, $directions[$i] ?? 'asc');
                    }
                }
                $i++;
            }
        } else {
            $this->baseQuery->orderBy($table . '.' . 'id', 'desc');
        }
    }

    /**
     * Verifica se esta filtrando registro ativo/inativo
     * @param Request $request
     */
    public function verifyActive(Request $request)
    {
        if ($request->exists('is_active')) {
            if (!$request->boolean('is_active')) {
                $this->baseQuery->onlyTrashed();
            }
        } else {
            $this->baseQuery->withTrashed();
        }
    }

    /**
     * Busca em todos os campos da tabela pela string enviada.
     * Função utiliza OR por padrão
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder|Model
     * @noinspection PhpUndefinedMethodInspection
     */
    public function advancedSearch(Request $request)
    {
        $input = $request->get('search');

        $this->verifyLimiter();

        $this->mountFieldsToSelect($request);

        $this->mountSelectToDates($request);

        $this->getWherehas($request, 'OR');

        foreach ($this->fieldSearchable as $column) {
            $type = $this->model()::getFieldType($column);
            if (!empty($type)) {
                if ($type == 'string') {
                    $this->baseQuery->orWhere($column, 'like', '%' . $input . '%');
                }
            }
        }
        $this->mountOrderBy($request);
        return $this->baseQuery->orderBy($this->model->getTable() . '.' . 'deleted_at');
    }

    /**
     * Função responsável por montar o where para tabelas relacionadas
     * de acordo com os parâmetros
     * @param Request $request
     * @param string $type
     * @noinspection PhpUndefinedMethodInspection
     */
    public function getWherehas(Request $request, string $type)
    {
        if ($type == 'AND') {
            foreach ($this->model->getRelationsBySearch() as $relation) {
                if (in_array(Str::snake($relation), $request->keys())) {
                    $classRelation = get_class($this->model->{Str::ucfirst($relation)}()->getRelated());
                    foreach ($request->get(Str::snake($relation)) as $key => $field) {
                        $this->baseQuery->whereHas(
                            $relation,
                            function ($query) use ($key, $field, $classRelation, $relation) {
                                $type = $classRelation::getFieldType($key);
                                $tableRelated = $this->model->{$relation}()->getRelated()->getTable();
                                if ($type) {
                                    if ($type == 'string') {
                                        $query->where($tableRelated . '.' . $key, 'like', '%' . $field . '%');
                                    } else {
                                        $query->where($tableRelated . '.' . $key, $field);
                                    }
                                }
                            }
                        );
                    }
                }
            }
        } else {
            foreach ($this->model->getRelationsBySearch() as $relation) {
                $this->baseQuery->orWhereHas($relation, function ($query) use ($request) {
                    $query->Where('name', 'like', '%' . $request->get('search') . '%');
                });
            }
        }
    }

    /**
     * Montar o array para sincronizar na tabela relacionada
     * montagem obrigatória para ManyToMany
     * @param array $input
     * @param string $fieldsInsert
     * @return string|array[]
     */
    public function mountValueRelation(array $input, string $fieldsInsert)
    {
        $type = '';
        foreach ($input as $value) {
            if (empty($type)) {
                $type = [$value[$fieldsInsert]];
            } else {
                $type[] = $value[$fieldsInsert];
            }
        }

        return $type;
    }

    /**
     * Cria a estrutura para sincronizar a tabela de ManyToMany
     * @param array $input Array de entrada do request
     * @param string $relation Nome da tabela de relação no SINGULAR
     * @return array Dados que serão sincronizados
     */
    public function createSync(array $input, string $relation)
    {
        $syncs = [];
        foreach ($input[Str::Plural($relation)] as $value) {
            $syncs[] = $value['id'];
        }
        return $syncs;
    }

    /**
     * Função para sincronizar dados das tabelas relacionadas
     * Seria o mesmo que o sync mas para relações hasMany
     * @param array $input
     * @param Model $baseModel
     * @throws \ReflectionException
     */
    public function syncHasMany(array $input, Model $baseModel)
    {
        foreach ($this->model->getRelationsBySearch() as $relation) {
            $id = [];
            if (isset($input[Str::snake($relation)]) && $baseModel->{$relation}() instanceof HasMany) {
                foreach ($input[Str::snake($relation)] as $value) {
                    $idInserted = $baseModel->{$relation}()->updateOrCreate(['id' => $value['id'] ?? null], $value);
                    $id[] = $idInserted->id;
                }
                $modelRelation = (new \ReflectionClass(
                    get_class($this->model->{Str::ucfirst($relation)}()->getRelated())
                ))
                    ->newInstanceWithoutConstructor()->newQuery();
                $modelRelation
                    ->whereNotIn('id', array_filter($id))
                    ->where(Str::singular($this->model->getTable()) . '_id', $baseModel->id);
                foreach ($modelRelation->get() as $value) {
                    if (!empty($value)) {
                        (new \ReflectionClass(get_class($this->model->{Str::ucfirst($relation)}()->getRelated())))
                            ->newInstanceWithoutConstructor()->newQuery()->find($value->id)->delete();
                    }
                }
            }
        }
    }

    /**
     * Função que faz a iteração dos dados
     * para inserir dados na tabelas relacionadas
     * @param array $input
     * @param Model $baseModel
     */
    public function variousCreateMany(array $input, Model $baseModel)
    {
        foreach ($this->model->getRelationsBySearch() as $relation) {
            if (isset($input[Str::snake($relation)]) && $baseModel->{$relation}() instanceof HasMany) {
                $baseModel->{$relation}()->createMany($input[Str::snake($relation)]);
            }
        }
    }

    /**
     * Método para executar limpeza de cache de qualquer model passada por parametro
     * @param Request $request
     * @return array
     */
    public function flushCache(Request $request)
    {
        $ret = [];
        foreach ($request->get('models') as $model) {
            $class = \App::make('\\App\\Models\\' . $model['name']);
            if ($class->cacheFor) {
                try {
                    $class->flushQueryCache();
                    $ret[$model['name']] = 'Limpeza de cache executado com sucesso';
                } catch (Throwable $e) {
                    $ret[$model['name']] = $e;
                }
            } else {
                $ret[$model['name']] = 'Não há cache para ser limpo';
            }
        }
        return $ret;
    }

    /**
     * Monta os campos passados por parametros para o select
     * Remove os campos que não fazem parte da Model para evitar quebra de SQL
     * @param Request $request
     */
    protected function mountFieldsToSelect(Request $request)
    {
        if ($request->exists('fields')) {
            $fields = explode(',', $request->get('fields'));
            foreach ($fields as $key => $field) {
                if (trim($field) == 'id') {
                    $fields[$key] = $this->model->getTable() . '.id';
                }
                if (!array_key_exists(trim($field), $this->model->getCasts())) {
                    unset($fields[$key]);
                }
            }
            $this->baseQuery->select(array_map('trim', $fields));
        }
    }

    /**
     * Monta o filtro por data tanto com Between como busca direta
     * @param Request $request
     */
    protected function mountSelectToDates(Request $request)
    {
        if ($request->exists('start_created_at')) {
            if ($request->exists('end_created_at')) {
                $this->baseQuery->whereBetween(
                    'created_at',
                    [
                        $request->get('start_created_at') . ' 00:00:00',
                        $request->get('end_created_at') . ' 23:59:00'
                    ]
                );
            } else {
                $this->baseQuery->whereDate(
                    'created_at',
                    $request->get('start_created_at')
                );
            }
        }
        if ($request->exists('start_updated_at')) {
            if ($request->exists('end_updated_at')) {
                $this->baseQuery->whereBetween(
                    'updated_at',
                    [
                        $request->get('start_updated_at') . ' 00:00:00',
                        $request->get('end_updated_at') . ' 23:59:00'
                    ]
                );
            } else {
                $this->baseQuery->whereDate(
                    'updated_at',
                    $request->get('start_updated_at')
                );
            }
        }
    }

    protected function verifyLimiter()
    {
        if ($this->model->hasCompanyId()) {
            $this->baseQuery->where(
                $this->model->getTable() . '.' . 'company_id',
                auth('api')->user()->company_id ?? 1
            );
        }
    }

    /**
     * Retorna as inciais de um nome/frase informado
     * @param string $value
     * @return string
     */
    protected function initials(string $value)
    {
        $words = explode(' ', $value);
        $initials = null;
        foreach ($words as $word) {
            $initials .= $word[0];
        }
        return strtoupper($initials);
    }
}
