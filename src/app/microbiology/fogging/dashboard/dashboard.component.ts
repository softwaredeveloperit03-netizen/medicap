import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
  providers:[DatePipe]
})
export class DashboardComponent implements OnInit {

  results;
  foggings;
  selectedResult=[];
  isView=false;
  from_date='';
  to_date='';
  today;
  
  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }


  ngOnInit(): void {
    this.getFogging();
  }
  getFogging(){
    this.service.get('microbiology/fogging.php?type=getFogging&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response=>{
      this.foggings=response;
    })
  }
  download(){
    this.service.open('microbiology/fogging.php?type=downloadFogging&id='+this.selectedResult['id'])
  }
  view(index){
    this.selectedResult=this.foggings[index];
    this.isView=true;
  }
  downloadLog(){
    this.service.open('microbiology/fogging.php?type=downloadLogFogging&from_date='+this.from_date+'&to_date='+this.to_date)
  }

}
