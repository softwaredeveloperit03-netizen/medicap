import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';



@Component({
  selector: 'app-finish',
  templateUrl: './finish.component.html',
  styleUrls: ['./finish.component.css']
})
export class FinishComponent implements OnInit {
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getEquipmentNames();
  }
  equipments;
  getEquipmentNames() {
    this.service.get('master/equipment.php?type=getEquipmentNames1').subscribe(response => {
      this.equipments = response;
      console.log('equipments',this.equipments);
    });
  }
  save(data){
    if(data.valid)
    this.service.post('qc/raw.php?type=save_finish',JSON.stringify(data.value)).subscribe(response=>{
      alert("saved succesfully")
      this.router.navigate(['/qc/anat1/finish'])
      data.reset();
    });
    else{
      alert("All filled required");
    }
  }
  
  

}
