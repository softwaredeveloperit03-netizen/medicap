import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-mrp-history',
  templateUrl: './mrp-history.component.html',
  styleUrls: ['./mrp-history.component.css']
})
export class MrpHistoryComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.service.get('qa/product.php?type=getMRPChangeHistory').subscribe(response=>{
      this.results=response;
    })
  }
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

}
