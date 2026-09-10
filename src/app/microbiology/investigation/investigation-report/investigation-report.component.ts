import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import {Router} from '@angular/router';
  declare let alertify;
@Component({
  selector: 'app-investigation-report',
  templateUrl: './investigation-report.component.html',
  styleUrls: ['./investigation-report.component.css']
})
export class InvestigationReportComponent implements OnInit {
  results;
  constructor(private service : DataAccessService,private router : Router) {
    
    }
  ngOnInit(): void {
    this.getInvestigationSampling();
  
  }
  getInvestigationSampling(){
    this.service.get('microbiology/investigationreport.php?type=getInvestigationSampling').subscribe(response =>{
      this.results = response; 
    });
  }

}
