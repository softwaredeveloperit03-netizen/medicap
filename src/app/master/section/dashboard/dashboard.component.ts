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


  results;
  sections;
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
   this.getSections();
  }
  



  getSections(){
    this.service.get('master/section.php?type=getSection').subscribe(response=>{
      this.results=response;
    })
    
  }


  download(){
    this.service.open('master/section.php?type=downloadsectionLog')
  }

 

}
