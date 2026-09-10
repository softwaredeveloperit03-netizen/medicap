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
    { data: 'a. Depressurized, Drained, Free from sludge & washed with water and brought to room temperature', status: '' },
    { data: 'b. Free from any extraneous matter-gas, fumes and dangerous liquid or any form of energy and is cleaned by disconnection & blinding.', status: '' },
    { data: 'c. Steamed and purged Air /Nitrogen, if required.', status: '' },
    { data: 'd. Tags and cautionary notices displayed and Area is barricaded.', status: '' },
    { data: '3. Inflammable/Combustible material removed from vicinity and drains are covered.', status: '' },
    { data: '4. Equipment venting into the building is shut off.', status: '' },
    { data: '5. Arrangements made for sufficient ventilation.', status: '' },
    { data: '6. Working area effectively protected, against falling sparks.', status: '' },
    { data: '7. Running water, fire extinguishers and fire water hose provided, tested and trained fire fighter kept ready    Name of fire fighter & Sign : - …………', status: '' },
    { data: '8 a. Gas test shows:a.	Flammable hydrocarbon ………... % LEL (Check LEL not to exceed by 30% of LEL of the Chemical). 	', status: '' },
    { data: '8 b.	Oxygen % ……………(Minimum % required 19.5% v/v). 	', status: '' },
    { data: '8 c.	Toxic gas (Name) ………………………………………….. Conc……………. 	', status: '' },
    { data: '9. Electrical / Mechanical / Pneumatic isolation done, checked, Tagged and LOTO done:- Yes …….. /No ……..   Tag No. ………..Fuses removed & LOTO done by :  Name & sign: ………………………………. Fuses kept with:     Name & sign: ………………………………….......................', status: '' },
    { data: '10. Proper means of entry & exit provided at all times.', status: '' },
    { data: '11. I (contractor/employee) conform that the related training is provided to the team of persons included in the work. The same was understood and will be followed.  Name & Sign of Contractor/Employee:.', status: '' },
    { data: '12. Additional Precautions taken .', status: '' }
  ];
  precautions1=[
    { data1: '1. Provisions made by the originating dept. checked and found acceptable to work.', status1: '' },
    { data1: '2. Safe working platform/ metallic ladder has been provided and is made available at all the times. Metallic scaffolding has a safe working load of …………..    kg. Mention if Tripod stand is provided and it is anchored properly and in good condition.', status1: '' },
    { data1: '3. Earthling cable has been connected only to the equipment being welded and within one meter from the height point.', status1: '' },
    { data1: '4. Welding machine, gas cutting set has been inspected and is in satisfactory and safe working condition.', status1: '' },
    { data1: '5. 24 volts hand lamp with glass& metallic cover has been provided.', status1: '' },
    { data1: '6. Shield against sparks provided.', status1: '' },
    { data1: '7. Use of Personal Protective Equipments:	 Helmets	, Safety Goggle	, Safety Shoes,	 Apron,	Face Shield	,Ear Plug/ Muff Pressure ,	Gas/Dust Mask	,Airline Respirator,	Hand gloves,	Safety belts', status1: '' },
    { data1: '8 Job performer: Name ------------------------ Sign ---------- Training imparted : Yes / No (mark as applicable) Contractor: ----------------. 	', status1: '' },
    { data1: '9 Standby person Mr. -------------------------- Sign -----------------', status1: '' },
    { data1: '10 Proper grounding/earthling, insulation of cables ensured. 	', status1: '' },
    { data1: '11. Suitable type Fire extinguisher Kept near By. Type:-                  ID. No.', status1: '' },
    { data1: '12. Tags & cautionary notices displayed. Area is barricaded .', status1: '' },
     { data1: '13. Additional Precautions taken .', status1: '' }
  ];


    constructor(private service: DataAccessService,private datePipe: DatePipe,private router:Router) {
      this.date = this.datePipe.transform(Date.now(), 'yyyy-MM-dd');
    }

    ngOnInit() {
      this.getDepartments();
       this.getEquipmentsLog();
      }
      equipments;
  getEquipmentsLog(){

    this.service.get('master/equipment.php?type=getallequipment').subscribe(response => {
      this.equipments = response;
      });

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
      this.service.post('ehs/EHS_hotwater.php?type=saveWork',JSON.stringify(temp)).subscribe(response => {
        if (response['status'] == 'success') {
         data.resetForm();
         this.router.navigate(['/ehs/hotwork'])
         alertify.success(this.service.t('common.savedSuccess'));
       } else {
         alertify.error(this.service.t('common.errorOccurred'));
       }
     });
    }
  }
