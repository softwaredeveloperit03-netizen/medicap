import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

 
  constructor(private service: DataAccessService) {
 
  }

  isView = false;
  results;

  selectedResult = [];

  ngOnInit(): void {
    this.getDQLog();
  }

  getDQLog(){
    this.service.get('qa/qualification.php?type=getDQLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  ViewCertificate() {
    window.open(this.service.url+'../../upload/dq/' + this.selectedResult['dq_file']);
  }
  

}
