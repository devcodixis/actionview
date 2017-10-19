<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Project\Eloquent\Board;
use App\Project\Eloquent\BoardRankMap;
use App\Project\Eloquent\AccessBoardLog;
use App\Project\Provider;

class BoardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($project_key)
    {
    }

    public function getList($project_key) {

$example = [ 
  'id' => '111',
  'name' => '1111111111',
  'subtask' => true,
  'query' => [],
  'rank' => [],
  'last_access_time' => 11111111,
  'columns' => [
    [ 'name' => '待处理', 'states' => [ 'Open', 'Reopened' ] ],
    [ 'name' => '处理中', 'states' => [ 'In Progess' ] ],
    [ 'name' => '关闭', 'states' => [ 'Resolved', 'Closed' ] ]
  ],
  'filters' => [
    [ 'id' => '11111', 'name' => '111111' ],
    [ 'id' => '22222', 'name' => '222222' ],
    [ 'id' => '33333', 'name' => '333333' ],
  ],
];

        return Response()->json([ 'ecode' => 0, 'data' => [ $example ] ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $project_key)
    {
        $name = $request->input('name');
        if (!$name || trim($name) == '')
        {
            throw new \UnexpectedValueException('the name can not be empty.', -12400);
        }

        $board = State::create([ 'project_key' => $project_key ] + $request->all());
        return Response()->json(['ecode' => 0, 'data' => $board]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $project_key, $id)
    {
        $board = Board::find($id);
        if (!$board || $project_key != $board->project_key)
        {
            throw new \UnexpectedValueException('the board does not exist or is not in the project.', -12402);
        }

        $updValues = [];
        $name = $request->input('name');
        if (isset($name))
        {
            if (!$name || trim($name) == '')
            {
                throw new \UnexpectedValueException('the name can not be empty.', -12400);
            }
            $updValues['name'] = $name;
        }

        $query = $request->input('query');
        if (isset($query))
        {
            $updValues['query'] = $query;
        }

        $columns = $request->input('columns');
        if (isset($columns))
        {
            $updValues['columns'] = $columns;
        }

        $subtask = $request->input('subtask');
        if (isset($subtask))
        {
            $updValues['subtask'] = $subtask;
        }

        $board->fill($updValues)->save();
        return Response()->json(['ecode' => 0, 'data' => Board::find($id)]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($project_key, $id)
    {
        $board = Board::find($id);
        //if (!$board || $project_key != $board->project_key)
        //{
        //    throw new \UnexpectedValueException('the board does not exist or is not in the project.', -10002);
        //}
        return Response()->json(['ecode' => 0, 'data' => $state]);
    }

    /**
     * rank the column issues 
     *
     * @param  string  $project_key
     * @param  string  $id
     * @return void
     */
    public function setRank($project_key, $id)
    {
        $col_no = $request->input('col_no');
        if (!isset($col_no))
        {
            throw new \UnexpectedValueException('the column no can not be empty.', -11500);
        }

        $parent = $request->input('parent');
        if (!$parent || trim($parent) == '')
        {
            $parent = '';
        }

        $rank = $request->input('rank');
        if (!isset($rank) || !$rank) 
        {
            throw new \UnexpectedValueException('the rank can not be empty.', -11500);
        }

        $old_rank = BoardRankMap::where([ 'board_id' => $id, 'col_no' => $col_no, 'parent' => $parent, 'rank' => $rank ])->first(); 
        $old_rank && $old_rank->delete();

        $rank = BoardRankMap::create([ 'board_id' => $id, 'col_no' => $col_no, 'parent' => $parent, 'rank' => $rank ]);

        return Response()->json(['ecode' => 0, 'data' => $rank ]);
    }

    /**
     * record user access board
     *
     * @param  string  $project_key
     * @param  string  $id
     * @return void
     */
    public function setAccess($project_key, $id) 
    {
        $record = AccessBoardLog::where([ 'board_id' => $id, 'user_id' => $this->user->id ])->first();
        $record && $record->delete();

        AccessBoardLog::create([ 'project_key' => $project_key, 'board_id' => $id, 'latest_access_time' => time() ]);
        return Response()->json(['ecode' => 0, 'data' => [ 'id' => $id ] ]);
    }
}