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
  plant_id:any;
  plant_type:any;
  // packing_List_All;
  packing_List_All = [];
  selectedResult = [];
  constructor(private service:DataAccessService) { 

  }

  ngOnInit() {
    this.getPendingUnitFormulas();
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.plant_type = this.service.getPlantConfigFields('plant_type');

    this.get_rights();
  }

  
  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' +        localStorage.getItem('emp_id')).subscribe(response  => {
      this.rights = response;
      this.isuser=this.rights[0].isuser
      this.ischecker=this.rights[0].ischecker
      this.isapprover=this.rights[0].isapprover
      this.qms_approver=this.rights[0].qms_approver
      this.dept_head=this.rights[0].dept_head
      this.isauditor=this.rights[0].isauditor
      this.plant_head=this.rights[0].plant_head
      this.shift_allocator=this.rights[0].shift_allocator
    });
  }

  getPendingUnitFormulas(){
    this.service.get('production/unitformula.php?type=getUnitFormulasforCheckingzUMA').subscribe(response => {
      this.results = response;
    });
  }

 
  groupedMaterials=[];
  primary_pm_list=[];
  consumeableMaterial=[];
  view(index) {

    this.groupedMaterials=[];
    this.selectedResult = this.results[index];
    this.consumeableMaterial=this.selectedResult['consumeableMaterial'];
    this.primary_pm_list=this.selectedResult['primary_pm_list'];

    const raw_materials = this.selectedResult['raw_materials'];

    this.groupedMaterials = raw_materials.reduce((group, material) => {
      const { stage } = material;
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});





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
