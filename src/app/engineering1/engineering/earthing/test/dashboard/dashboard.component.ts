import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  lists;
  isView=false;

  selectedResult = [];
  constructor(private service: DataAccessService) { 
   
  }

  ngOnInit() {
    this.getList();
  }
  getList(){
    this.service.get('engineering/earthing.php?type=getEarthingTests').subscribe(response=>{
      this.lists=response;
    });
  }
  downloadLog(){
    this.service.open('engineering/earthing.php?type=downloadTestEarthingPoints');
  }
  view(index){
    this.selectedResult=this.lists[index];
    this.isView=true;
  }
}
