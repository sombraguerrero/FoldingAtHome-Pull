sl $PSScriptRoot
Try
{
$myinfo = Invoke-RestMethod -Method GET -Uri 'http://stats.foldingathome.org/api/donor/3107'
$guru3d_info = $myinfo.teams[2];
$baseURL = 'http://settersynology/boinc/graphql_fah.php'
$query = '{"query": "mutation MutateStat($in_ltw:String, $in_rank:Int, $in_tc:Int, $in_twu:Int) {writeStat(last_team_wu : $in_ltw, rank : $in_rank, team_credit: $in_tc, team_work_units: $in_twu) {last_team_wu rank team_credit team_work_units}}",'
$vars = '"variables":' + "{`"in_ltw`":`"$($guru3d_info.last)`",`"in_rank`":$($myinfo.rank),`"in_tc`":$($guru3d_info.credit),`"in_twu`":$($guru3d_info.wus)}}"
$body = $($query + $vars)
$createdStat = Invoke-RestMethod -Method POST -Uri $baseURL -Body $body
Out-File -FilePath "info.log" -InputObject $($createdStat.data.writeStat) 
}
Catch
{
"[$(Get-Date -Format g)] $($_.Exception.Message)" | Out-File "error.log" -Append
}