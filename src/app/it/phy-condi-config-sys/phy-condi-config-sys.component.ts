  import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-phy-condi-config-sys',
  templateUrl: './phy-condi-config-sys.component.html',
  styleUrls: ['./phy-condi-config-sys.component.css']
})
export class PhyCondiConfigSysComponent implements OnInit {



   isView = false;
   isNew = false;
   results;

    constructor(private service:DataAccessService) { }

    ngOnInit(): void {
      this.getDepartments();
      this.getPhyCondi();
    }

    departments;
    getDepartments() {
        this.service.get('it/it.php?type=getDepartments').subscribe((response: any) => {
        this.departments = response;
      });
    }

    getPhyCondi() {
        this.service.get('it/it.php?type=getPhyCondi').subscribe((response: any) => {
        this.results = response;
      });
    }



    selectedResult = [];
    view(index){
      this.isView =  true ;
      this.isNew =  false ;
      this.selectedResult = this.results[index];
    }

  remark = '';


    savephyCondi(data){

      if(!data.valid){
        alertify.error("All FIel Required !!!!!!!!");
        return;
      }

      let temp = data.value;

      this.service.post('it/it.php?type=savephyCondi', JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
          this.getPhyCondi();
          this.isNew = false;
          data.reset();

          } else {
          alert('Failed: An error occured, please try again!');
        }
      });
    }

  }
