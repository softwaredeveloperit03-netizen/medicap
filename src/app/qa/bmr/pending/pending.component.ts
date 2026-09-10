import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { MEDICAP_PRODUCTION_FLOW, offerNextStep } from 'src/app/shared/medicap-production-flow';
declare let alertify;

@Component({
  selector: 'app-pending',
  templateUrl: './pending.component.html',
  styleUrls: ['./pending.component.css']
})
export class PendingComponent implements OnInit {

  products;
  results;
  isNew = false;
  batch_no = '';
  materials = []; 
  work_order_raw_materials = [];
  work_order_packing_materials = [];
  selectedResult = [];
  work_order_lots=[];
  pack_sizes=[];
  person;
  plant_type='';
  plant_id='';
  material_type='RM';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingBatchNos();
    this.getQAPerson();
    this.plant_type = this.service.getPlantConfigFields("plant_type")
    this.plant_id = this.service.getPlantConfigFields("plant_id")
  }
  getQAPerson(){
    this.service.get('employee.php?type=getQAPersons').subscribe(response=>{
      this.person=response;
    });
  }
  getPendingBatchNos(){
    this.service.get('production/workorder1.php?type=get_approved_work_orders_for_qa_approval').subscribe(response => {
      this.results = response || [];
      // Check approval status for all work orders at once
      this.checkAllLineApprovalStatus();
    });
    // this.service.get('production/plan.php?type=getPendingBatchNos').subscribe(response => {
    //   this.results = response;
    // });
  }
  
  // Check if all work orders are approved in line_booking (from lineapproval component)
  checkAllLineApprovalStatus() {
    if (!this.results || this.results.length === 0) {
      return;
    }
    
    // Get all work order numbers
    const workOrderNos = this.results
      .map((r: any) => r.work_order_no)
      .filter((wo: string) => wo && wo.trim() !== '');
    
    if (workOrderNos.length === 0) {
      // No work orders, mark all as not approved
      this.results.forEach((result: any) => {
        result.lineApproved = false;
        result.lineApprovalStatus = 'Pending';
      });
      return;
    }
    
    // Check all work orders at once by getting all booked lines
    // We'll check if any of the work orders have status='Booked' in line_booking
    this.service.get(`bmr/line_booking.php?type=getBookingHistory&status=Booked`).subscribe((response: any) => {
      const approvedWorkOrders = new Set<string>();
      
      // Create a set of approved work order numbers
      if (response && Array.isArray(response)) {
        response.forEach((booking: any) => {
          if (booking.workorder_no && booking.status === 'Booked') {
            approvedWorkOrders.add(booking.workorder_no);
          }
        });
      }
      
      // Update each result with approval status
      this.results.forEach((result: any) => {
        result.lineApproved =
          this.isFromBatchPlanning(result) ||
          approvedWorkOrders.has(result.work_order_no);
        result.lineApprovalStatus = result.lineApproved ? 'Approved' : 'Pending';
      });
    }, (error) => {
      // On error, mark all as not approved
      this.results.forEach((result: any) => {
        result.lineApproved = this.isFromBatchPlanning(result);
        result.lineApprovalStatus = result.lineApproved ? 'Approved' : 'Pending';
      });
    });
  }

  // Line booking (fproduction/mfglines) only covers order-based work orders, which
  // live in Work_order_materials. Work orders created from a batch plan never appear
  // there, so they must not be gated on it.
  isFromBatchPlanning(result: any): boolean {
    const planId = result && result.batch_plan_id;
    return planId !== null && planId !== undefined && String(planId).trim() !== '' && String(planId).trim() !== '0';
  }
  
  // Check if work order is approved from line approval
  isLineApproved(result: any): boolean {
    return result && result.lineApproved === true;
  } 
  groupedMaterials =[];
  view(index) {
    this.groupedMaterials =[];
    this.selectedResult = this.results[index]; 
    this.material_type =  this.selectedResult['material_type']; 
    this.materials = this.results[index]['materials'];
    this.pack_sizes = this.selectedResult['pack_sizes'];
    this.work_order_lots = this.results[index]['lots'];
    this.work_order_raw_materials = [];
    this.work_order_packing_materials = [];
    for (let x = 0; x < this.materials.length; x++) {
      if (this.materials[x]['material_type'] == 'Packing Material'&& this.materials[x]['material_code'] !== '' ) {
        console.log('hi');
        this.work_order_packing_materials.push(this.materials[x]);
      } else if( this.materials[x]['material_code'] !== ''  ) {
        this.work_order_raw_materials.push(this.materials[x]);
        console.log('hello');
      }
    }




    this.groupedMaterials = this.work_order_raw_materials.reduce((group, material) => {
      const { stage } = material;
      group[stage] = group[stage] ?? [];
      group[stage].push(material);
      return group;
    }, {});





    this.isNew = true;
  }
  mfg;
  exp;
  allocate(status,data) {
    if(!data.valid){
      alertify.success('All fields are required');     
      return;
    }
    let obj = {
      "id" : this.selectedResult['id'],
      "product_name" : this.selectedResult['product_name'],
      "batch_type" : this.selectedResult['batch_type'],
      "short_code" : this.selectedResult['short_code'],
      "status" : status,
      "stability" : data.value['stability'],
      "stability_reason" : data.value['stability_reason'],
      "process_validation" : data.value['process_validation'],
      "hard_copy_issued" : data.value['hard_copy_issued'],
      "ebmr_number" : data.value['ebmr_number'],
      "hard_copy_issued_by" : data.value['hard_copy_issued_by'],
      "batch_number" : data.value['batch_number'],
      "mfg_date" : this.mfg,
      "exp_date" : this.exp
    }
    this.service.post('production/workorder1.php?type=update_qa_status',JSON.stringify(obj)).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('Status has been saved successfully'); 
        this.getPendingBatchNos();       
        this.isNew = false;
        if (status === 'approve' || status === 'Approved' || status === 'APPROVED') {
          offerNextStep(
            MEDICAP_PRODUCTION_FLOW.productionBatchQa,
            'Production → Batch QA (send dispense request)'
          );
        }
      } else {
        alertify.error('Failed: '+response['status']);
      }
    });
    // this.service.post('production/plan.php?type=allocateBatchNo&batch_no=' + this.batch_no + '&id=' + this.selectedResult['id'] + '&status=' + status,JSON.stringify(this.selectedResult)).subscribe(response => {
    //   if (response['status'] == 'success') {
    //     alert('Medicap Lot No Allocated Successfully');
    //     this.getPendingBatchNos();
    //     this.isNew = false;
    //   } else {
    //     alert(response['status']);
    //   }
    // });
  }

}