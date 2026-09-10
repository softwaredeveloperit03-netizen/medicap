import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-completed',
  templateUrl: './completed.component.html',
  styleUrls: ['./completed.component.css']
})
export class CompletedComponent implements OnInit {
  
  isView = false;
  results: any = [];
  selectedResult =[];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getdatalist();
  }
  getdatalist(){
    this.service.get('packing/process.php?type=getCompletedBatches').subscribe(response =>{
      this.results = response;
    })
  }

  view(index){
    this.isView = true;
    this.selectedResult = this.results[index];
  }

}
