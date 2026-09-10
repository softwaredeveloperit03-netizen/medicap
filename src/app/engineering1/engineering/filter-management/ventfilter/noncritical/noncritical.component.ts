import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-noncritical',
  templateUrl: './noncritical.component.html',
  styleUrls: ['./noncritical.component.css']
})
export class NoncriticalComponent implements OnInit {

  results;
  constructor(private service : DataAccessService) {
   }

  ngOnInit(): void {
 
    this.getCriticalFilter();
  }

  getCriticalFilter(){
    this.service.get('engineering/ventfilter.php?type=getNonCriticalFilter').subscribe(response =>{
      this.results = response;
    });
  }
 
  download(){
    this.service.open('engineering/ventfilter.php?type=downloadNonCriticalFilter')
  }
}