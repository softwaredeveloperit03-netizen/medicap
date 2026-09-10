  import { DatePipe } from '@angular/common';
  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;    

@Component({
  selector: 'app-disposal',
  templateUrl: './disposal.component.html',
  styleUrls: ['./disposal.component.css'],
  providers:[DatePipe]     
})
export class DisposalComponent implements OnInit {
    from_date = '';
    to_date = '';
    today = '';
    results;
    selectedResult=[];
    isAwaiting = false;
    requests;
    constructor(private service : DataAccessService,private datePipe :DatePipe) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
  
    ngOnInit(): void {
      this.getDisposalRecords();
    }
    getDisposalRecords(){
      this.service.get('microbiology/media.php?type=getDisposalRecords&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
        this.results = response;
      });
    }
    download(){
      this.service.open('microbiology/media.php?type=downloadDisposalRecords&from_date='+this.from_date+'&to_date='+this.to_date)
    }
  
  }
  