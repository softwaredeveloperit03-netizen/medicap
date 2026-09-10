  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  import { DatePipe } from '@angular/common';
  declare let alertify;
@Component({
  selector: 'app-culture-maintainance',
  templateUrl: './culture-maintainance.component.html',
  styleUrls: ['./culture-maintainance.component.css'],
  providers: [DatePipe]

})
export class CultureMaintainanceComponent implements OnInit {

    from_date = '';
    to_date = '';
    today = '';
    results;
  
    constructor(private service: DataAccessService, private datePipe: DatePipe) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
  
    ngOnInit(): void {
      this.getCulture();
    }
  
    getCulture() {
      this.service.get('microbiology/culture.php?type=getCulture&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
        this.results = response;
      });
    }
    download() {
      this.service.open('microbiology/culture.php?type=downloadCulture&from_date=' + this.from_date + '&to_date=' + this.to_date)
    }
    saveCulture(data) {
      if (!data.valid) {
        alertify.error('All fields are required');
        return;
      }
      this.service.post('microbiology/culture.php?type=saveCulture', JSON.stringify(data.value)).subscribe(response => {
        if (response['status'] === 'success') {
          this.getCulture();
          alertify.success('Record Inserted successfully');
          data.resetForm();
        } else {
          alertify.error(response['status']);
        }
      });
    }
  }