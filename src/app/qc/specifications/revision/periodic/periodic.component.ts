import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-periodic',
  templateUrl: './periodic.component.html',
  styleUrls: ['./periodic.component.css']
})
export class PeriodicComponent implements OnInit {
  results;
  isView=false;
  selectedSpec = [];

  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPeriodicRevisions();
  }
  getPeriodicRevisions(){
    this.service.get('qc/specification/raw.php?type=getPeriodicRevisions').subscribe(response=>{
      this.results=response;
    });

  }
  view(index) {
    this.isView = true;
    this.selectedSpec = this.results[index];
  }



}
