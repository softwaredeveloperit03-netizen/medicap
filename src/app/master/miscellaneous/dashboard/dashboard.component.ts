import { QcDeptCard } from 'src/app/shared/qc-module-dashboard/qc-module-dashboard.models';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { ModalService } from 'src/app/_modal/modal.service';
declare let alertify;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  })
export class DashboardComponent implements OnInit {
  cards: QcDeptCard[] = [
    { id: 'tools', title: 'General Materials', route: 'tools', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' },
    { id: 'tools', title: 'Spares/Accessories', route: 'tools', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #4facfe 0%, #764ba2 100%)' },
    { id: 'tools', title: 'UOM', route: 'tools', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #43e97b 0%, #764ba2 100%)' },
    { id: 'tools', title: 'Grade', route: 'tools', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fa709a 0%, #764ba2 100%)' },
    { id: 'tools', title: 'Storage Condition', route: 'tools', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #30cfd0 0%, #764ba2 100%)' },
    { id: 'tools', title: 'Shapes', route: 'tools', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #e0c3fc 0%, #764ba2 100%)' },
    { id: 'tools', title: 'GST', route: 'tools', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #fbc2eb 0%, #764ba2 100%)' },
    { id: 'tools', title: 'Pack Size', route: 'tools', icon: 'fa-th-large', category: 'Modules', gradient: 'linear-gradient(135deg, #ff6e7f 0%, #764ba2 100%)' },
  ];


  criterias;
  selectedResult = [];
  genMaterialSubList = [];
  isEdit = false;
  company_type = 'API/Excipients';
  gst_type = 'CGST/SGST';
  is_logs = false;
  material_type = false;
  fg_type_api_inter = false;
  fg_type_formulation = false;
  other_type = true;
  other_master_type = 'General Material'
  constructor(private service: DataAccessService, private router: Router, private modalService: ModalService) { }

  ngOnInit(): void {


  }

  select_panel(idx) {
    switch (idx) {
      case 0:
        this.is_logs = true;
        this.material_type = false;
        this.fg_type_api_inter = false;
        this.other_type = false;
        break;
      case 1:
        this.material_type = true;
        this.is_logs = false;
        this.fg_type_api_inter = false;
        this.other_type = false;
        break;
      case 2:
        this.fg_type_api_inter = true;
        this.is_logs = false;
        this.material_type = false;
        this.other_type = false;
        break;
      case 3:
        this.other_type = true;
        this.is_logs = false;
        this.material_type = false;
        this.fg_type_api_inter = false;

        break;
    }
  }
  saveMaterialType(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['plant_id'] = localStorage.getItem("plant_id");
    this.service.post('master/materialtype.php?type=saveMaterialtype', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }

  openModal(id: string) {
    this.modalService.open(id);
  }

  closeModal(id: string) {
    this.modalService.close(id);
  }
}
