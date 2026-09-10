import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {

  isView = false;
  updatelabs: any[] = [];
  selectresult: any = {};
  index = 0;
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getPendingLabs();
  }

  getPendingLabs(){
    this.service.get('qc/lab.php?type=getPendingLabs').subscribe((response: any) =>{
      this.updatelabs = Array.isArray(response) ? response : [];
    });

    }
  view(index){
      this.selectresult = this.updatelabs[index] || {};
      this.isView = true;
  }

  get branchRows(): any[] {
    const raw = this.selectresult?.branch ?? this.selectresult?.branches;
    if (!raw) {
      return [];
    }
    if (Array.isArray(raw)) {
      return raw;
    }
    if (typeof raw === 'string') {
      try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
      } catch {
        return [];
      }
    }
    return [];
  }

  viewCertifidate(){
    let url = this.service.url+this.selectresult['certificate'] ;
    window.open(url, '_blank');
  }

  viewLic(){
    let url = this.service.url+this.selectresult['lic'] ;
    window.open(url, '_blank');
  }
  updateLab(status) {
    this.service.get('qc/lab.php?type=updateLab&status='+status+'&id='+this.selectresult['id']).subscribe(response =>{
      if(response['status']=='success'){
        alertify.success("Recored Updated Succesfully");
        this.isView = false;
        this.getPendingLabs();
      }else{
        alertify.error("Failed to Update Record");
      }
    });
  }
  }


