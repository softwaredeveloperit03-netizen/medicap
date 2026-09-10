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

  location;
  frequency;
  frequency_bath;
  frequency_cabin;
  users;
  statements;
  takings;
  results;
  makes;

  constructor(private service: DataAccessService, private router: Router) { }
 
  ngOnInit() {
    this.getData();
    this.getCabin();
    this.getbath();
    this.getfactory();
    this.getmake();
  }
  getData() {
   
    this.service.get('admin/housekeeping.php?type=get_office').subscribe(response => {
      this.users = response;
    
    });
  }

  getCabin() {
   
    this.service.get('admin/housekeeping.php?type=get_cabin').subscribe(response => {
      this.statements = response;
    
    });
  }
  download1(){
    this.service.open('admin/housekeeping.php?type=download_personal_hygiene')
  }
  download2(){
    this.service.open('admin/housekeeping.php?type=cabin_passage')

  }
  download3(){
    this.service.open('admin/housekeeping.php?type=download_personal_hygiene')

  }
  
  
  getbath() {
   
    this.service.get('admin/housekeeping.php?type=get_bath').subscribe(response => {
      this.takings = response;
    
    });
  }

  getfactory() {
   
    this.service.get('admin/housekeeping.php?type=get_factory').subscribe(response => {
      this.results = response;
    
    });
  }

  getmake() {
   
    this.service.get('admin/housekeeping.php?type=get_general').subscribe(response => {
      this.makes = response;
    
    });
  }

}
