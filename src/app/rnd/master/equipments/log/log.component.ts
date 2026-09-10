import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';


@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  isView = false;
  results;
  equipment_name = '';
  equipment_type = '';
  status='';
  selectedResult = [];
  departs;
  department_name='';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getEquipmentsLog();
    this.getDepart();
  }

  getEquipmentsLog(){
    this.service.get('qa/equipments.php?type=getEquipmentsLog&equipment_type='+ this.equipment_type +'&equipment_name='+ this.equipment_name + '&status='+this.status+'&department_name='+this. department_name).subscribe(response=>{
      this.results=response;
    });
  }
  download(){
    this.service.open('qa/equipments.php?type=downloadEquipmentsLog&equipment_type='+ this.equipment_type +'&equipment_name='+ this.equipment_name + '&status='+this.status+'&department_name='+this. department_name)
  }
  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  edit() {
    this.router.navigate(['/equipments/edit/' + this.selectedResult['id'] + '/' + this.selectedResult['equipment_type'] + '/' + this.selectedResult['department'] + '/' + this.selectedResult['section']]);
  }
 getDepart(){
   this.service.get('common.php?type=getDepartments').subscribe(response=>{
     this.departs=response;
   })

  }

}
