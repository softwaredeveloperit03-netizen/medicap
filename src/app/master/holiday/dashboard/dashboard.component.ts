import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  holidays;
  selectedResult = [];
  isEdit = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getHoliday();
  }

  getHoliday(){
    this.service.get('master/holiday.php?type=getHoliday').subscribe(response => {
      this.holidays = response;
    })
  }

  edit(index) {
    this.selectedResult = this.holidays[index];
    console.log(this.selectedResult);
    this.isEdit = true;
  }

  editHoliday() {
    this.service.post('master/holiday.php?type=editHoliday&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Udated successfully');
        this.isEdit = false;
        this.getHoliday();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  delHoliday(id){
    this.service.get('master/holiday.php?type=deleteHoliday&id=' + id).subscribe(response => {
      if (response['status']=="success") {
        alertify.success(this.service.t('common.deletedSuccess'));
        this.getHoliday();
      } else {
        alertify.error('some error occured');
      }
    });
  }

}
