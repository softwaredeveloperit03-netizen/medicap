import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  isView = false;
  results;
  packingList;
  raw_materials;
  selectedResult = [];
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingUnitFormulas();
  }

  getPendingUnitFormulas(){
    this.service.get('production/unitformula.php?type=getUnitFormulasforChecking').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.raw_materials = JSON.parse(this.selectedResult['raw_materials']);
    this.packingList = JSON.parse(this.selectedResult['packing_materials']);
    this.isView = true;
  }

  approveUnitFormula(status) {
    this.service.get('production/unitformula.php?type=checkingUnitFormula&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if(response['status'] == 'success'){
        alert('Data Updated Successfully!');
        this.isView = false;
        this.getPendingUnitFormulas();
      }else{
        alert('An Error Occured, Please try again!');
      }
    });
  }

}
