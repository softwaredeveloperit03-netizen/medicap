import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-critical',
  templateUrl: './critical.component.html',
  styleUrls: ['./critical.component.css'],
})
export class CriticalComponent implements OnInit {

    results;
      constructor(private service : DataAccessService) {
       }
    
      ngOnInit(): void {
     
        this.getCriticalFilter();
      }
    
      getCriticalFilter(){
        this.service.get('engineering/ventfilter.php?type=getCriticalFilter').subscribe(response =>{
          this.results = response;
        });
      }
     
      download(){
        this.service.open('engineering/ventfilter.php?type=downloadCriticalFilter')
      }
    }