import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-sterilization',
  templateUrl: './sterilization.component.html',
  styleUrls: ['./sterilization.component.css'],
  providers:[DatePipe]
})
export class SterilizationComponent implements OnInit {

  from_date = '';
  to_date = '';
  results;
  isNew=false;
  labours;
  lafs;
  balances;
  equipments;
  constructor(private service : DataAccessService,private datePipe :DatePipe) {
    this.from_date = this.datePipe.transform(Date.now(), 'yyyy-MM-01');
    this.to_date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

  ngOnInit(): void {
    this.getAutoclave();
    this.getLabours();
  }

  getAutoclaves() {
    this.service.get('equipments.php?type=getAutoclaves').subscribe(response => {
      this.equipments = response;
    });
  }

  getLabours(){
    this.service.get('common.php?type=getOperators').subscribe(response =>{
      this.labours = response;
    });
  }
 
  getAutoclave(){
    this.service.get('microbiology/autoclave.php?type=getPendingAutoclave&from_date='+this.from_date+'&to_date='+this.to_date).subscribe(response =>{
      this.results = response;
    });
  }
  
  download(){
    this.service.open('microbiology/autoclave.php?type=downloadPendingAutoclave&from_date='+this.from_date+'&to_date='+this.to_date)
  }

}
