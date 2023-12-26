const mariadb = require('mariadb');
const https = require('https');
require('dotenv').config();

async function PostStat() {
	let conn;
	var insertResult;
	try
	{
		const getOptions = {
			hostname: 'api2.foldingathome.org',
			path: '/uid/3107',
			method: 'GET'
		};
		//Perform GET request with specified options.
		let foldingData = '';
		const fahReq = https.request(getOptions, (fahRes) => {
			fahRes.on('data', (res) => { foldingData += res; });
			fahRes.on('end', async function() {
				var myStats = JSON.parse(foldingData);
				const conn = await mariadb.createConnection({
					socketPath: process.env.socketPath, 
					user: process.env.user, 
					password: process.env.password,
					database: process.env.database
				});
				var currentTeam = myStats.teams.find((t) => t.team == 238643);
				var lastWU = currentTeam.last == 0 ? myStats.last : currentTeam.last;
				conn.query("INSERT INTO fah_stats (ID, last_team_wu, rank, team_score, team_work_units, name) values (ordered_uuid(UUID()), ?, ?, ?, ?, ?)", [lastWU, myStats.rank, currentTeam.score, currentTeam.wus, currentTeam.name])
				.then((resp) => { 
					console.log(resp);
					return conn.end();
				});
				
			});
			
			fahRes.on('error', (err) => {
				console.log(err);
			});
		}).end();
		
		fahReq.on('error', (err) => {
			console.log(err);
		});
	}
	catch (err)
	{
		console.log(err);
	}
	finally
	{
		if (conn)
			return conn.end();
	}
}

return PostStat();