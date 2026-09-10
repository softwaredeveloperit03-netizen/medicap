import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-temperature',
  templateUrl: './temperature.component.html',
  styleUrls: ['./temperature.component.css'],
  providers: [DatePipe]
})
export class TemperatureComponent implements OnInit {
  from_date = '';
  to_date = '';
  today = '';
  results;
  plant_names;

  constructor(private service: DataAccessService, private datePipe: DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit(): void {
    this.getTempRecords();
  }

  getTempRecords() {
    this.service.get('microbiology/temperature_refrigerator.php?type=getTemperature&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  download() {
    this.service.open('microbiology/temperature_refrigerator.php?type=downloadTemperature&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  saveTempRecords(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/temperature_refrigerator.php?type=saveTemperature', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.getTempRecords();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
}