import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-mat-rate',
  templateUrl: './mat-rate.component.html',
  styleUrls: ['./mat-rate.component.css']
})
export class MatRateComponent implements OnInit {

 
   
   constructor(private service: DataAccessService) { }
 
   ngOnInit() {
    this.getActiveClient();
   }

 
    clients;
    getActiveClient() {
        this.service.get('marketing/client.php?type=getActiveClient').subscribe(response => {
          this.clients = response;
        });
    }

    clientsSubGrps;
    getClientSeries(client_code) {
        this.service.get('marketing/client.php?type=getClientSeries&client_code='+client_code).subscribe(response => {
          this.clientsSubGrps = response;
        });
    }
 

    selectedMaterial = {};
    isAddAmendRate=false;
    addAmendRate(data:any){
      this.isAddAmendRate=true;
      this.selectedMaterial=data;
    }


    

}
