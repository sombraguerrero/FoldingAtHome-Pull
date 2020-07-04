'use strict';
const https = require('https');
const http = require('http');

// Stage Get request to retrieve data from FAH API
https.get('https://stats.foldingathome.org/api/donor/3107', (res) => {
  const { statusCode } = res;
  const contentType = res.headers['content-type'];

  let error;
  if (statusCode !== 200) {
    error = new Error('Request Failed.\n' +
      `Status Code: ${statusCode}`);
  } else if (!/^application\/json/.test(contentType)) {
    error = new Error('Invalid content-type.\n' +
      `Expected application/json but received ${contentType}`);
  }
  if (error) {
    console.error(error.message);
    // Consume response data to free up memory
    res.resume();
    return;
  }

  // Stage GraphQL post data for request
  res.setEncoding('utf8');
  let rawData = '';
  res.on('data', (chunk) => { rawData += chunk; });
  res.on('end', () => {
    try {
      const postData = JSON.parse(rawData);
      const teamData = postData.teams[2];
      var myQuery = "{\"query\": \"mutation MutateStat($in_ltw:String, $in_rank:Int, $in_tc:Int, $in_twu:Int) {writeStat(last_team_wu : $in_ltw, rank : $in_rank, team_credit: $in_tc, team_work_units: $in_twu) {last_team_wu rank team_credit team_work_units}}\",";
      var varObj = { in_ltw: teamData.last, in_rank: postData.rank, in_tc: teamData.credit, in_twu: teamData.wus };
      var jsonVars = JSON.stringify(varObj) + '}';
      var myVars = "\"variables\":" + jsonVars;
      const writeData = myQuery + myVars;

      const options = {
        hostname: 'settersynology',
        path: '/boinc/graphql_fah.php',
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(writeData)
        }
      };

      const req = http.request(options, (res) => {
        console.log(`STATUS: ${res.statusCode}`);
        console.log(`HEADERS: ${JSON.stringify(res.headers)}`);
        res.setEncoding('utf8');

        res.on('data', (chunk) => {
          console.log(`BODY: ${chunk}`);
        });
        res.on('end', () => {
          console.log('No more data in response.');
        });
      });

      req.on('error', (e) => {
        console.error(`problem with request: ${e.message}`);
      });

      // Write data to request body
      req.write(writeData);
      req.end();
      console.log(writeData);
    } catch (e) {
      console.error(e.message);
    }
  });
}).on('error', (e) => {
  console.error(`Got error: ${e.message}`);
});
