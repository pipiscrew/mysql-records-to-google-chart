<?php
require_once('general.php');

$mysql = new dbase();
$mysql->connect_mysql();

$rows_USERS = $mysql->getSet("select USER_NAME from jobs group by USER_NAME order by USER_NAME", null);

$rows_COUNTRIES = $mysql->getSet("select COUNTRY from jobs group by COUNTRY order by COUNTRY", null); 

$max_date = $mysql->getScalar("select DATE_FORMAT(max(DATE_CREATED),'%d/%m/%Y') from jobs", null);

$dtp_max_date = $mysql->getScalar("select DATE_FORMAT(max(DATE_CREATED),'%Y-%m-%d') from jobs", null);

?>


<html>
  <head>
      

        <script src="assets/jquery-3.1.1.min.js"></script>
        <script src="assets/bootstrap.min.js"></script>
        <script src="assets/bootstrap-selector.js"></script>
        <script src="assets/bootstrap-datepicker.min.js"></script>
        <link href="assets/bootstrap.min.css" rel="stylesheet">
        <link href="assets/bootstrap-datepicker3.min.css" rel="stylesheet">

        <script>
            $(function() {
                //////////////// SELECTOR
                //**attach the event
                $('#jobs_users').chooser();
                $('#jobs_countries').chooser();
            
                
                
                //**fill USER list by PHP/jSON Array
                var jArray_USERS = <?php echo json_encode($rows_USERS); ?> ;

                if (jArray_USERS)
                {
                    $("#jobs_users").fillList(jArray_USERS, "Users", "USER_NAME", "USER_NAME");
                    $("#jobs_users").setSelected(jArray_USERS, "USER_NAME");
                }
                
                
                //**fill COUNTRIES list by PHP/jSON Array
                var jArray_COUNTRIES = <?php echo json_encode($rows_COUNTRIES); ?> ;

                if (jArray_COUNTRIES)
                {
                    $("#jobs_countries").fillList(jArray_COUNTRIES, "Countries", "COUNTRY", "COUNTRY");
                }
                
                
                
                //////////////// DATEPICKER
                $('#dtp_start, #dtp_end').datepicker({
                    format: 'yyyy-mm-dd',
    				 startDate: "-3m",
					 endDate: "-1d",
                    autoclose: true
                });

				
				 //////////////// SELECTOR + - 
				//select all users
				$('#btn_jobs_users_select_all').on('click', function(e) {
					e.preventDefault();
					
					$('#jobs_users').setAll(true);
				});
				
				//deselect all users
				$('#btn_jobs_users_deselect_all').on('click', function(e) {
					e.preventDefault();
					
					$('#jobs_users').setAll(false);
				});
				
				//select all countries
				$('#btn_jobs_countries_select_all').on('click', function(e) {
					e.preventDefault();
					
					$('#jobs_countries').setAll(true);
				});
				
				//deselect all countries
				$('#btn_jobs_countries_deselect_all').on('click', function(e) {
					e.preventDefault();
					
					$('#jobs_countries').setAll(false);
				});
			
            });
            
            //**before form submit
            function validate(){

                if (!$("#dtp_start").val() || !$("#dtp_end").val())
                {
                    alert("Please set date range");
                    return false;
                }
                
                //get #selected users#
                var users = $("#jobs_users").getSelected();
                $("#users").val(users);
                
                //get #selected countries#
                var countries = $("#jobs_countries").getSelected();
                $("#countries").val(countries);

                if (!users[0] && !countries[0])
                {
                    alert("Please choose users or/and countries");
                    return false;
                }
                
                
                return true;
            }
            
            
        </script>
<body>
      <div class="container">
          <div class="panel panel-primary"> 
              <div class="panel-heading"> 
                  <h3 class="panel-title">Report Options (<?=$max_date?>) : </h3> 
              </div> 
              <div class="panel-body"> 
                  <form id="theform" method="post" action="analysis.php" onsubmit="return validate()">
                      <div class="row">
                           <div class="col-md-6">
                                <!-- https://github.com/uxsolutions/bootstrap-datepicker-->
                                <div class="input-daterange input-group" id="datepicker">
                                    <input type="text" class="input-sm form-control" id="dtp_start" name="dtp_start" value=""/>
                                    <span class="input-group-addon">to</span>
                                    <input type="text" class="input-sm form-control" id="dtp_end" name="dtp_end" value="<?=$dtp_max_date?>"/>
                                </div>
                           </div>
                          <div class="col-md-6">
                               <button class="btn btn-success" style="float:right" type="submit">search</button>
                          </div>
                      </div>
                      
                      <div class="row" style="margin-top:10px">
                        <div class="col-md-6">
							<button class="btn btn-success" id="btn_jobs_users_select_all" style="margin-bottom:10px">+</button>
							<button class="btn btn-success" id="btn_jobs_users_deselect_all" style="margin-bottom:10px">-</button>
							
                            <div id="jobs_users" class="list-group centre" style="width:150px"></div>
                            <input id="users" name="users" type="hidden">
                        </div>
                        <div class="col-md-6">
							<button class="btn btn-success" id="btn_jobs_countries_select_all" style="margin-bottom:10px">+</button>
							<button class="btn btn-success" id="btn_jobs_countries_deselect_all" style="margin-bottom:10px">-</button>
							
                            <div id="jobs_countries" class="list-group centre" style="width:250px"></div>
                            <input id="countries" name="countries" type="hidden">
                        </div>
                      </div>
                  </form>
              </div> 
          </div>
      </div>
      </body>
    </head>
</html>  