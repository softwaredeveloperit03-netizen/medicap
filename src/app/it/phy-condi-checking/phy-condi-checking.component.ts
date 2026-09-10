  import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-phy-condi-checking',
  templateUrl: './phy-condi-checking.component.html',
  styleUrls: ['./phy-condi-checking.component.css']
})
export class PhyCondiCheckingComponent implements OnInit {


   isView = false;
   results;



    constructor(private service:DataAccessService) { }


    ngOnInit(): void {
      this.getPhyCondiByStatus();
    }

    getPhyCondiByStatus() {
        this.service.get('it/it.php?type=getPhyCondiByStatus&status=Pending').subscribe((response: any) => {
        this.results = response;
      });
    }



    selectedResult = [];
    view(index){
      this.isView =  true ;
      this.selectedResult = this.results[index];
    }

    remark = '';


    checkPhyCondiOfSystem(data){

      let temp = data.value;
      temp['id'] = this.selectedResult['id'];

      this.service.post('it/it.php?type=checkPhyCondiOfSystem', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Checked Successfully');
          this.getPhyCondiByStatus();
          this.isView = false;
          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }

  }
