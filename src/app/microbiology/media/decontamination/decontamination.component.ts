  import { DatePipe } from '@angular/common';
  import { Component, OnInit } from '@angular/core';
  import { DataAccessService } from 'src/app/data-access.service';
  declare let alertify;  

@Component({
  selector: 'app-decontamination',
  templateUrl: './decontamination.component.html',
  styleUrls: ['./decontamination.component.css'],
  providers:[DatePipe]    
})
export class DecontaminationComponent implements OnInit { 
    from_date = '';
    to_date = '';
    today = '';
    results;
    selectedResult=[];
    constructor(private service : DataAccessService,private datePipe :DatePipe) {
      this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
      this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
      this.today = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }
  
    ngOnInit(): void {
      this.getHdpe();
    }
    getHdpe(){
    /*   this.service.get('microbiology/hdpe.php?type=&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
        this.results = response;
      }); */
    }
    download(){
      this.service.open('microbiology/hdpe.php?type=&from_date='+this.from_date+'&to_date='+this.to_date)
    }
  }
  