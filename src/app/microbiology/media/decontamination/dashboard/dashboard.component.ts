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
    from_date = '';
    to_date = '';
    today = '';
    results;
    selectedResult=[];
    isAwaiting = false;
    requests;
    isView = false;

    constructor(private service : DataAccessService,private datePipe :DatePipe) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
    ngOnInit(): void {
      this.getDecontaminationRecords();
    }
    getDecontaminationRecords(){
      this.service.get('microbiology/media.php?type=getDecontaminationRecords&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
        this.results = response;
      });
    }
    view(index){
      this. selectedResult = this.results[index];
      this.isView = true;
    }
    download(){
      this.service.open('microbiology/media.php?type=downloadDecontaminationRecords&from_date='+this.from_date+'&to_date='+this.to_date)
    }
    download1(){
      this.service.open('microbiology/media.php?type=downloadDecontaminationRecord&id='+this.selectedResult['id'])
    }
  }
  