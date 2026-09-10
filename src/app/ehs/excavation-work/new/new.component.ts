import { Component, OnInit } from '@angular/core';
import {DatePipe} from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
  providers:[DatePipe]

})
export class NewComponent implements OnInit {
    date;
    departments;

   form: any = {
    plant: '',
    location: '',
    date: '',
    validFrom: '',
    validTo: '',
    criticalJob: '',
    permissionTo: '',
    jobDescription: '',
    originDept: '',
    executingDept: '',
    hodEngineering: '',
    ehsDept: ''
  };
  plant=localStorage.getItem('')

  precautions=[
    { data: '1. Equipment/work area Inspected', status: '' },
    { data: '2. Equipment/Pipelines have been:', status: '' },
    { data: 'a. Depressurized, Drained, Free from sludge & washed with water', status: '' },
    { data: 'b. Free from gas, fumes, liquids & energy. Cleaned by disconnection & blinding.', status: '' },
    { data: 'c. Steamed and purged with inert gas if required.', status: '' },
    { data: 'd. Tags and caution notices displayed; area barricaded.', status: '' },
    { data: '3. Proper entry/exit available.', status: '' },
    { data: '4. Equipment venting into building is shut off.', status: '' },
    { data: '5. Adequate ventilation provided.', status: '' },
    { data: '6. Use of Personal Protective Equipments:	 Helmets	 Safety Goggle	 Safety Shoes	 Apron	Face Shield	Ear Plug/ Muff Pressure suit	Gas/Dust Mask	Airline Respirator	Hand gloves	Safety belts Full Body harness	Fall Arrester	Life Line', status: '' },
    { data: '7. Electrical / Mechanical / Pneumatic isolation done, checked, Tagged / LOTO done. :  Yes ……..    /   No …….. Fuses removed / LOTO done by (Name & Sign): ……………………………     Tag No. ………...  Fuses kept with       Name & sign: …………………………………...', status: '' },
    { data: '8. Check with persons working at height are not suffering from dizziness, If Yes get other person. 	', status: '' },
    { data: '9. Safety belt with proper anchorage is used (Full body Harness with double Lanyard) and stand by person available.Above 10 mtrs at height additional Shock Absorber or Fall retardant system with Full Body Harness is required.', status: '' },
    { data: '10. Training provided to employee/contractor.', status: '' },
    { data: '11. Securely supported ladder is provided & placed at and angle of 75 degree to the horizontal and 01 meter above the top surface.', status: '' },
    { data: '12. Metallic Scaffolding is provided and safe working load is :…………   kgs,   Certified By: Name & Sign:………………………..      (Metallic scaffolding or Ladder not permitted in Electrical jobs /areas).', status: '' },
    { data: '13. Working platform on Scaffolding is properly tied at all four corners.', status: '' },
    { data: '14. Railing is provided on top of Scaffolding, where person is standing for work.', status: '' },
    { data: '15. Portable scaffolding wheels are locked and / or provided with stoppers.', status: '' },
    { data: '16. For fragile roofs  Travel restraint system is provided with adequate anchorage and safety belts and can withstand potential loadings or provided with roof ladder.', status: '' },
    { data: '17. Underground Cables, pipelines identified and certified to work to avoid damage and safety risk. Specify cables/Pipelines at depth of ….. ….. …mtrs Certified by: Name & Signature of Civil & Electrical engineer Immacule: ………………………………… ……………………………..', status: '' },
    { data: '18. Name & Sign of Job Performer: …………………………………….Stand by Person (Name &Sign.):…………………………...', status: '' },
    { data: '19. I (contractor/employee) conform that the related training is provided to us and we understood the same.  Name & Sign of Contractor/Employee: …………………………………………………………………', status: '' },
    { data: '20. Other Precautions if any.', status: '' }
  ];


    constructor(private service: DataAccessService,private datePipe: DatePipe,private router:Router) {
      this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

    ngOnInit() {
      this.getDepartments();
      }


    getDepartments(){
      this.service.get('common.php?type=getDepartments').subscribe(response=>{
        this.departments = response;
      })
    }
    saveData(data){
      let temp = data.value;
      temp['precautions']=this.precautions;
      console.log('temp :>> ', temp);
      this.service.post('ehs/excavation.php?type=saveExcavation',JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         data.resetForm();
         this.router.navigate(['/ehs/excavation-work'])
         alertify.success(this.service.t('common.savedSuccess'));
       } else {
         alertify.error(this.service.t('common.errorOccurred'));
       }
     });
    }
  }
