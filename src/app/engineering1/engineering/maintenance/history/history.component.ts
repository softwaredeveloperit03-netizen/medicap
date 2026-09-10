import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css'],
  providers:[DatePipe]
})
export class HistoryComponent implements OnInit {

  isView = false;
  results;
  departments;
  department_name = '';
  from_date = '';
  to_date = '';

  selectedResult = [];
  constructor(private service:DataAccessService,private datePip:DatePipe) { 
    this.from_date = this.datePip.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePip.transform(Date.now(), 'yyyy-MM-dd');
  }

  ngOnInit() {
    this.getMaintenanceHistory();
    this.getDepartments();
  }

  getDepartments(){
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getMaintenanceHistory(){
    this.service.get('engineering/maintenance.php?type=getMaintenanceHistory&department_name=' + this.department_name + '&from_date=' + this.from_date + '&to_date=' + this.to_date).subscribe(response => {
      this.results = response;
    });
  }
  getMaintenanceDetails() {
    this.service.get('engineering/maintenance.php?type=getMaintenanceDetails&maintenance_no='+this.selectedResult['maintenance_no']).subscribe((response: any) => {
      this.selectedResult = response;
    });
  }
  viewfile(link) {
    window.open(this.service.url + 'upload/maintenance/' + link);
  }


  download(){
    this.service.open('engineering/maintenance.php?type=downloadMaintenanceHistory&department_name=' + this.department_name + '&from_date=' + this.from_date + '&to_date=' + this.to_date)  }
  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }
  downloadView(){
    this.service.open('engineering/maintenance.php?type=downloadMaintenance&id='+this.selectedResult['id'])  }
}
