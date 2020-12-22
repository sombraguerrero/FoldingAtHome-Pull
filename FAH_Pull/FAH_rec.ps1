sl $PSScriptRoot
Try
{
$myinfo = Invoke-RestMethod -Method GET -Uri 'http://stats.foldingathome.org/api/donor/3107'
$tcg_info = $myinfo.teams[1];
$baseURL = 'http://settersynology/boinc/graphql_fah.php'
$query = '{"query": "mutation MutateStat($in_ltw:String, $in_rank:Int, $in_tc:Int, $in_twu:Int, $in_tn:String) {writeStat(last_team_wu : $in_ltw, rank : $in_rank, team_credit: $in_tc, team_work_units: $in_twu, equipo_nombre: $in_tn) {last_team_wu rank team_credit team_work_units equipo_nombre}}",'
$vars = '"variables":' + "{`"in_ltw`":`"$($tcg_info.last)`",`"in_rank`":$($myinfo.rank),`"in_tc`":$($tcg_info.credit),`"in_twu`":$($tcg_info.wus),`"in_tn`":`"$($tcg_info.name)`"}}"
$body = $($query + $vars)
Invoke-RestMethod -Method POST -Uri $baseURL -Body $body
#Out-File -FilePath "info.log" -InputObject $($createdStat.data.writeStat) 
}
Catch
{
"[$(Get-Date -Format g)] $($_.Exception.Message)" | Out-File "error.log" -Append
}