import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DatePipe } from '@angular/common';
declare let alertify;
@Component({
  selector: 'app-cleaning',
  templateUrl: './cleaning.component.html',
  styleUrls: ['./cleaning.component.css'],
  providers: [DatePipe]
})
export class CleaningComponent implements OnInit {
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
    this.getCleaningrefrigerator();
  }

  getCleaningrefrigerator() {
    this.service.get('microbiology/refrigerator.php?type=getPendingRefrigerator&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  download() {
    this.service.open('microbiology/refrigerator.php?type=downloadPendingRefrigerator&from_date=' + this.from_date + '&to_date=' + this.to_date)
  }
  saveCleaningrefrigerator(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/refrigerator.php?type=saveRefrigerator', JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] === 'success') {
        this.getCleaningrefrigerator();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
}