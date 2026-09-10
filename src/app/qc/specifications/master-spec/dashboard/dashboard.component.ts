import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  results;
  plant_id='';
  isView=false;
  is_corporate='';
  selectedResult=[];
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.plant_id = localStorage.getItem("plant_id");
    this.is_corporate = localStorage.getItem("is_corporate");
    this.getProducts();
  }

  getProducts() {
      this.service.get('').subscribe((response: any) => {
        this.results = response;
      });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  
  

  




}
