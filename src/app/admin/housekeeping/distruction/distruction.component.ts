import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-distruction',
  templateUrl: './distruction.component.html',
  styleUrls: ['./distruction.component.css']
})
export class DistructionComponent implements OnInit {

  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {
    this.getDepartments();
  }
  departments;
  getDepartments(){
    this.service.get('qa/all2.php?type=getDepartments').subscribe((response:any) => {
      this.departments = response;
     
    });
  }

  save(data) {
    
   this.service.post('qa/all.php?type=savedistruction_record_form',JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
      // this.router.navigate(['/checklist']);
    } else {
      console.log(response);
      alert('Failed: An error occured, please try again!');
    }
  });
}

}
