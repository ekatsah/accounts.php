<?
/* Copyright 2012 Steak
 * 
 * This file is under the BeerWare License (rev. 42)
 * As long as you retain this notice you can do whatever you want with 
 * this stuff. If we meet some day, and you think this stuff is worth it, 
 * you can buy me a beer in return.
 * 
 * This file is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * 
 */

$magic_key = "nonpsychologic introspectives!";
$url = "/accounts.php";

$model = Array('id' => 'integer primary key autoincrement',
               'host' => 'text',
               'login' => 'text',
               'email' => 'text',
               'secret' => 'text',
               'notes' => 'text',
               'magic' => 'text',
               'update' => 'text',
               'visibility' => 'integer');

function get($a, $n) {
    if (isset($a->{$n}))
        return str_replace('"', '', str_replace('\\', '', $a->{$n}));
    else
        return '';
}

function model_format($name, $type) {
    return '"' . $name . '" '. $type;
}

function model_validate() {
    global $model;
    $array = json_decode(file_get_contents("php://input"));
    $values = Array();
    foreach ($model as $field => $type)
        $values[$field] = get($array, $field);
    $values['update'] = date("d/m/y");
    return $values;
}

// Create database if url is accounts.php?action=create
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && 
                                           $_GET['action'] == 'create') {
    $db = new SQLite3('accounts.db');
//    insanely dangerous!
//    $db->exec('drop table if exists records');
    $db->exec('create table records ('.implode(', ', array_map('model_format', 
                    array_keys($model), array_values($model))).');');
    $db->close();
    die('New db created, <a href="'.$url.'">go back</a>.');


// Manage ajax request from backbone
} else if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    $db = new SQLite3("accounts.db");

    switch($_SERVER['REQUEST_METHOD']) {
        case 'PUT':
            $record = model_validate($record);
            $db->exec('update records set visibility = 0 where "id" = "'.
                      $record['id'].'";');
            unset($record['id']);
            $db->exec('insert into records ("'.implode('", "', array_keys($record)).
                      '") values ("'.implode('", "', array_values($record)).'");');
            $record['id'] = $db->lastInsertRowID();
            $record['edit'] = false;
            $record['passphrase'] = false;
            die(json_encode($record));

        case 'GET':
            $results = $db->query("select * from records where visibility");
            $records = Array();
            while($res = $results->fetchArray(SQLITE3_ASSOC)) {
                $res['edit'] = false;
                $res['passphrase'] = false;
                array_push($records, $res);
            }
            die(json_encode($records));
    }

// Output application
} else { ?>

<html>
    <head>
        <title>Level 1 &amp; 2</title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script src="/scripts/jquery-1.7.min.js"></script>
        <script src="/scripts/underscore-min.js"></script>
        <script src="/scripts/backbone-min.js"></script>
        <script src="/scripts/handlebars-1.0.0.beta.6.js"></script>
        <script src="/scripts/aes.js"></script>
        <!-- from  http://www.movable-type.co.uk/scripts/aes.html -->

        <style type="text/css">
            body {
                margin: 10px;
                padding: 0px;
                font-family: arial, verdana;
                font-size: 10px;
            }
            
            h1 {
                margin: 20px 0px;
                font-size: 14px;
            }
            
            table {
                border-collapse: collapse;
                font-size: 10px;
            }
            
            th {
                margin: 1px;
                background-color: #91DAFF;
                padding: 4px 10px;
                border: 2px #DFECFF solid;
                min-width: 80px;
            }
            
            td {
                border: 2px #DFECFF solid;
                padding: 4px 10px;
                text-align: center;
            }
            
            th:first-child {
                min-width: 0px;
            }

            .more {
                cursor: pointer;
                text-align: center;
                background-color: #DFECFF;
            }
            
            input, textarea {
                border: 1px #5A9FFF solid;
                border-radius: 4px;
                margin: 1px;
                padding: 2px 4px;
                width: 100px;
                font-size: 10px;
            }
            
            textarea {
                height: 18px;
            }
            
            .action {
                cursor: pointer;
                color: #237FFF;
            }
            
            .button {
                border-radius: 3px;
                padding: 5px 13px 6px 13px;
                margin: 10px 5px 10px 0px;
                cursor: pointer;
            }

            .lbutton {
                border-top-left-radius: 3px;
                border-bottom-left-radius: 3px;
                padding: 5px 13px 6px 13px;
                margin: 10px 0px;
                cursor: pointer;
                border: 1px #CDBE70 solid;
                background-color: #FFEC8B;
            }

            .rbutton {
                border-top-right-radius: 3px;
                border-bottom-right-radius: 3px;
                padding: 5px 13px 6px 13px;
                margin: 10px 9px 10px 0px;
                border-top: 1px #CDBE70 solid;
                border-bottom: 1px #CDBE70 solid;
                border-right: 1px #CDBE70 solid;
                background-color: #FFEC8B;
            }

            .lock {
                border: 1px #6E8B3D solid;
                background-color: #BCEE68;
            }
                        
            .unlock {
                border: 1px #8B3A3A solid;
                background-color: #FF6A6A;
            }
            
            .mask {
                color: white;
                background-color: white;
            }
            
            .mask:hover {
                color: black;
            }
            
            .specin {
                border-top: 1px #8B3A3A solid;
                border-bottom: 1px #8B3A3A solid;
                border-right: 1px #8B3A3A solid;
                background-color: #FF6A6A;
                padding: 5px 7px 6px 7px;
            }

            .specin input {
                border-color: #8B3A3A;
            }
        </style>

        <script id="page_template" type="text/x-handlebars-template">
            <h1>Access Manager</h1>
            <span class="lbutton rpw">
                rpw
            </span><span class="rbutton rvalue">
                {{ password }}
            </span> 
            {{#each passphrases }}
            <span class="button lock" data-id="{{ this.id }}">
                lock {{ this.letter }}..</span> 
            {{/each}} 
            <span class="lbutton unlock">
                unlock
            </span><span class="rbutton specin">
                <input type="password" id="password"/>
            </span>
            <h1>Records</h1>
            <table>
                <thead><tr>
                    <th>#</th>
                    <th>Host</th>
                    <th>Login</th>
                    <th>Email</th>
                    <th>Secret</th>
                    <th>Notes</th>
                    <th>Last Update</th>
                </tr></thead>
                {{#each records }}
                <tr data-id="{{ this.id }}">
                    {{#if this.edit }}
                    <td>[ <span class="action put">put</span> ]</td>
                    <td><input type="text" name="host" value="{{ this.host }}"/></td>
                    <td><input type="text" name="login" value="{{ this.login }}"/></td>
                    <td><input type="text" name="email" value="{{ this.email }}"/></td>
                    <td><input class="mask" type="text" name="unsecret" value="{{ this.unsecret }}"/></td>
                    <td><textarea name="notes">{{ this.notes }}</textarea></td>
                    <td></td>
                    {{else}}
                    <td>{{#if this.passphrase }}
                        [ <span class="action edit">edit</span> ]
                        {{else}}
                        [ <span class="action show">show</span> ]
                    {{/if}}</td>
                    <td>{{ this.host }}</td>
                    <td>{{ this.login }}</td>
                    <td>{{ this.email }}</td>
                    <td class="mask">{{ this.unsecret }}</td>
                    <td>{{multiline this.notes }}</td>
                    <td>{{ this.update }}</td>
                    {{/if}}
                </tr>
                {{/each}}
                {{#if add}}
                <tr id="more_tr">
                    <td colspan="7" class="more">
                        More
                        <select id="passphrase">
                            {{#each passphrases }}
                            <option value="{{ this.id }}">{{ this.letter }}..</option>
                            {{/each}}
                        </select>
                    </td>
                </tr>
                {{/if}}
            </table>
        </script>

        <script type="text/javascript">
            application = Backbone.View.extend({
                initialize: function() {
                    _.bindAll(this, 'render', 'add', 'edit', 'put', 'change',
                              'unlock', 'lock', 'rpw', 'get_pass', 'show');
                    this.passphrases = [];
                    this.magic = '<? echo $magic_key ?>'; 
                    this.records = new Backbone.Collection();
                    this.records.url = '<? echo $url ?>';
                    this.records.on("reset add sync change:edit", this.render);
                    this.records.fetch();
                    this.password = "---";
                    this.pid = 1;
                },

                events: {
                    "click .rpw": "rpw",
                    "click .lock": "lock",
                    "click .unlock": "unlock",
                    "click .more": "add",
                    "click .show": "show",
                    "click .edit": "edit",
                    "click .put": "put",
                    "change": "change",
                    "click #passphrase": function() { return false; },
                    "submit .new_row": function() { return false; },
                },
                
                render: function() {
                    $(this.el).html(template({
                        'records': this.records.toJSON(),
                        'passphrases': this.passphrases,
                        'add': (this.passphrases.length > 0),
                        'password': this.password,
                    }));
                },

                get_pass: function(id) {
                    return _.find(this.passphrases, function(pp) {
                        return pp.id == id; 
                    });
                },

                add: function() {
                    var eid = 1+ Math.max.apply(null, this.records.pluck('id'));
                    if (eid < 0) eid = 1;
                    var pp = this.get_pass($("#passphrase").val());

                    var record = new Backbone.Model({
                        "visibility": 1,
                        "edit": true,
                        "id": eid,
                        "passphrase": pp.id,
                        "magic": Aes.Ctr.encrypt(this.magic, pp.pwd, 256),
                    });
                    record.url = '<? echo $url ?>';
                    this.records.add(record);
                    return false;
                },
             
                edit: function(e) {
                    var record = this.records.get($(e.target).parents("tr").
                                                           attr("data-id"));
                    record.set({
                        unsecret: Aes.Ctr.decrypt(record.get('secret'), 
                            this.get_pass(record.get('passphrase')).pwd, 256),
                        edit: true,
                    });
                    return false;
                },
                
                put: function(e) {
                    var row = $(e.target).parents('tr');
                    var eid = row.attr('data-id');
                    row.find('input,textarea').attr('readonly', true);
                    this.records.get(eid).set('unsecret', '');
                    this.records.get(eid).save();
                    return false;
                },
                
                change: function(e) {
                    if (e.target.id == 'passphrase') return false;
                    if (e.target.id == 'password') return this.unlock();

                    var name = $(e.target).attr('name');
                    var value = $(e.target).val();
                    var record = this.records.get($(e.target).parents('tr').
                                                           attr('data-id'));

                    record.set(name, value);
                    if (name == 'unsecret') {
                        var sec = Aes.Ctr.encrypt(value, 
                            this.get_pass(record.get('passphrase')).pwd, 256);
                        record.set('secret', sec);
                    }
                    return false;
                },
                
                unlock: function() {
                    var self = this, pwd = $('#password').val();
                    if (pwd == null || pwd == '') return false;
                    $('#password').val('');
                    var ci = Aes.Ctr.encrypt(this.magic, pwd, 256);

                    this.passphrases.push({
                        id: this.pid,
                        pwd: pwd,
                        letter: pwd[0],
                    });
                    ++this.pid;
                    this.render();
                    return false;
                },
                
                show: function(e) {
                    var record = this.records.get($(e.target).parents("tr").
                                                           attr("data-id"));
                    
                    var self = this;
                    _.each(this.passphrases, function(pp) {
                        if (Aes.Ctr.decrypt(record.get('magic'), pp.pwd, 
                                            256) == self.magic) {
                            record.set({
                                'passphrase': pp.id,
                                'unsecret': Aes.Ctr.decrypt(record.get('secret'), 
                                                            pp.pwd, 256),
                            });
                        }
                    });
                    if (record.get('passphrase') == false)
                        alert("passphrase not found");
                    else
                        this.render();
                    return false;
                },
                
                lock: function(e) {
                    var ppid = $(e.target).attr("data-id");
                    this.passphrases = _.reject(this.passphrases, function(e) {
                        return e.id == ppid;
                    });

                    this.records.each(function(record) {
                        if (record.get('passphrase') == ppid)
                            record.set({
                                'passphrase': false,
                                'edit': false,
                                'unsecret': '',
                            }, {
                                "silent": true,
                            });
                    });
                    this.render();
                },
                
                rpw: function() {
                    var chars = 'abcdefghijklmnopqrstuvwxyz' + 
                                'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
                    var x, password = '+';
                    for (x = 0; x < 14; x++)
                        password += chars.charAt(Math.floor(Math.random() *62));
                    this.password = password;
                    this.render();
                    return false;
                },
            });

            $(document).ready(function() {
                template = Handlebars.compile($("#page_template").html());
                Handlebars.registerHelper('multiline', function(object) {
                    return new Handlebars.SafeString(object.replace(/\n/g, "<br>"));
                });
                app = new application({el: "body"});
            });
        </script>
    </head>
    <body>Suddenly, zoidberg!</body>
</html>

<? } ?>
