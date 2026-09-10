import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;
  selectStage=[];
  selectEquiment=[];
  dosages;
  product_type='';
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getMFRLog();
    this.getDosages();
  }

  
  getDosages(){
    this.service.get('common.php?type=getDosages').subscribe(response=>{
      this.dosages=response;
    });
  }


  getMFRLog(){
    this.service.get('production/master.php?type=getMFRLog&product_type='+this.product_type).subscribe(response=>{
      this.results=response;
    });
  }
  view(index){
    this.selectedResult=this.results[index];
    this.selectStage=this.selectedResult['stages'];
    this.selectEquiment=this.selectStage[index];
    console.log(this.selectEquiment);
    this.isView=true;
  }
  download(){
    this.service.open('production/master.php?type=downloadMFRLog&product_type='+this.product_type);
  }
}
