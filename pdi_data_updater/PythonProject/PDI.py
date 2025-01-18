import yfinance as yahoo
import sys
import json
from datetime import datetime, timedelta

phpData = sys.argv[1]
ticker = "PDI"

phpData = phpData.strip("'")
phpData = phpData.rstrip()
phpDate = datetime.strptime(phpData, '%Y-%m-%d').date()
startDate = phpDate + timedelta(days=1)

endDate = datetime.today() + timedelta(days=1)
endDateFormatted = endDate.strftime('%Y-%m-%d')

yesterday = datetime.today() - timedelta(days=1)
yesterdayFormatted = yesterday.strftime('%Y-%m-%d')

if endDate.weekday() in range(0, 5):
    data = yahoo.download(ticker, start=startDate, end=endDateFormatted)
    data['Date'] = data.index.strftime('%Y-%m-%d')
    data = data[['Date', 'Open', 'High', 'Low', 'Close', 'Volume']]
    json_data = data.to_json(orient='records', date_format='iso')
    print(json_data)