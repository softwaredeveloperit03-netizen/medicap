import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-lineapproval',
  templateUrl: './lineapproval.component.html',
  styleUrls: ['./lineapproval.component.css']
})
export class LineapprovalComponent implements OnInit {
  
  constructor(private service: DataAccessService) { }
  
  loading: boolean = false;
  logLoading: boolean = false;
  approvalList: any[] = [];
  selectedWo: any = null;
  isView2: boolean = false;
  isShortages: boolean = false;
  shortageMaterials: any[] = [];
  verifyingStock: { [key: string]: boolean } = {}; // Track which WO is being verified
  logData: any[] = []; // Store log data
  logDataBackup: any[] = []; // Backup for log filtering
  logSearchText: string = ''; // Search text for log tab
  searchText = '';
  currentPage = 1;
  pageSize = 25;
  totalRecords = 0;
  logCurrentPage = 1;
  logPageSize = 25;
  logTotalRecords = 0;
  private searchDebounceTimer: ReturnType<typeof setTimeout> | null = null;
  private logSearchDebounceTimer: ReturnType<typeof setTimeout> | null = null;
  
  ngOnInit() {
    this.getParkedWorkOrders();
  }

  trackByWo(index: number, wo: any) {
    return wo?.id || wo?.workorder_no || index;
  }

  getBomBatchSize(wo: any): string {
    const kg = Number(wo?.batch_size_kg || wo?.batch_size || wo?.plan_qty || 0);
    if (!kg || kg <= 0) {
      return '—';
    }
    return kg.toLocaleString(undefined, { maximumFractionDigits: 3 }) + ' KGS';
  }

  woDeliveryDate(wo: any): string {
    const raw = wo?.deliveryDate || wo?.delivery_date || '';
    if (!raw || raw === '0000-00-00' || raw === '0000-00-00 00:00:00') {
      return '';
    }
    return String(raw);
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  logStartSrNo(): number {
    return (this.logCurrentPage - 1) * this.logPageSize;
  }

  private unwrapWoPage(response: any): { rows: any[]; total: number } {
    const rows = Array.isArray(response)
      ? response
      : (response?.work_orders || response?.data || []);
    const list = Array.isArray(rows) ? rows : [];
    const total = Array.isArray(response)
      ? list.length
      : Number(response?.total ?? list.length) || 0;
    return { rows: list, total };
  }

  private buildQueueUrl(): string {
    let url =
      'bmr/line_booking.php?type=getParkedWorkOrders&page=' +
      this.currentPage +
      '&limit=' +
      this.pageSize;
    const q = (this.searchText || '').trim();
    if (q) {
      url += '&search=' + encodeURIComponent(q);
    }
    return url;
  }

  private buildLogUrl(): string {
    let url =
      'bmr/line_booking.php?type=getApprovedWorkOrdersLog&page=' +
      this.logCurrentPage +
      '&limit=' +
      this.logPageSize;
    const q = (this.logSearchText || '').trim();
    if (q) {
      url += '&search=' + encodeURIComponent(q);
    }
    return url;
  }

  onSearchChange(): void {
    if (this.searchDebounceTimer) {
      clearTimeout(this.searchDebounceTimer);
    }
    this.searchDebounceTimer = setTimeout(() => {
      this.currentPage = 1;
      this.getParkedWorkOrders();
    }, 350);
  }

  onPageChange(page: number): void {
    if (!page || page === this.currentPage) {
      return;
    }
    this.currentPage = page;
    this.getParkedWorkOrders();
  }

  onPageSizeChange(size: number): void {
    if (!size || size === this.pageSize) {
      return;
    }
    this.pageSize = size;
    this.currentPage = 1;
    this.getParkedWorkOrders();
  }
  
  // Get parked work orders for approval
  getParkedWorkOrders() {
    this.loading = true;
    this.service.get(this.buildQueueUrl()).subscribe((response: any) => {
      if (response && !Array.isArray(response) && response.status === 'error') {
        this.approvalList = [];
        this.totalRecords = 0;
        this.loading = false;
        if (response.message) {
          alert('Unable to load line approval queue: ' + response.message);
        }
        return;
      }
      const page = this.unwrapWoPage(response);
      this.approvalList = page.rows;
      this.totalRecords = page.total;
      this.approvalList.forEach((wo: any) => {
        this.initializeStockStatus(wo);
      });
      if (this.approvalList.length === 0 && this.currentPage > 1 && this.totalRecords > 0) {
        this.currentPage -= 1;
        this.getParkedWorkOrders();
        return;
      }
      this.loading = false;
    }, () => {
      this.approvalList = [];
      this.totalRecords = 0;
      this.loading = false;
    });
  }
  
  // Initialize stock status based on existing deductions
  initializeStockStatus(wo: any) {
    if (!wo.workorder_no) return;
    
    // If deductions exist, check them
    if (wo.Deductions && wo.Deductions.length > 0) {
      this.processDeductions(wo);
    } else {
      // No deductions yet - mark as pending verification
      wo.stockComplete = false;
      wo.hasShortage = false;
      wo.stockVerified = false;
    }
  }
  
  // Process deductions to determine stock status
  processDeductions(wo: any) {
    let hasShortage = false;
    let indentRaised = false;
    wo.shortageMaterials = [];
    
    wo.Deductions.forEach((ded: any) => {
      const required = Number(ded.requiredQty || ded.plan_qty || 0)
        || (Number(ded.deducted_from_RM || 0) + Number(ded.shortage || 0));
      ded.requiredQty = required;
      ded.deducted_from_MC = 0;

      const shortage = Number(ded.shortage || 0);
      const available = Number(ded.current_total_available || ded.current_available_RM || 0);
      const stockAvailable = shortage <= 0;

      if (!stockAvailable && shortage > 0) {
        hasShortage = true;
        const indentStatus = ded.indent_status || 'Not Raised';
        const indentExistsInIndendRaw = ded.indent_exists_in_indend_raw || false;
        const isIndentRaised = indentStatus === 'Raised' || indentStatus === 'Indent Sent' || indentExistsInIndendRaw;
        const indentNo = ded.indent_no_from_indend_raw || ded.indent_no || '';
        const indentId = ded.indent_id_from_indend_raw || ded.indent_id || '';

        wo.shortageMaterials.push({
          material_code: ded.material_code || '',
          material_name: ded.material_name || '',
          mat_type: ded.mat_type || '',
          unit: ded.unit || '',
          requiredQty: required,
          deducted_from_RM: Number(ded.deducted_from_RM || 0),
          shortage: shortage,
          stockAvailable: stockAvailable,
          current_available_RM: available,
          current_total_available: available,
          indent_status: indentStatus,
          indent_raised: isIndentRaised,
          indent_id: indentId,
          indent_no: indentNo,
          indent_exists_in_indend_raw: indentExistsInIndendRaw
        });

        if (isIndentRaised) {
          indentRaised = true;
        }
      }
    });
    
    wo.hasShortage = hasShortage;
    wo.stockComplete = !hasShortage;
    wo.stockVerified = true; // Deductions exist, so stock has been verified
    wo.indentRaised = indentRaised; // Track if indent is already raised (only relevant if stock is not available)
  }
  
  // Refresh work order deductions to get latest indent_status
  refreshWorkOrderDeductions(wo: any) {
    // Fetch fresh data from getParkedWorkOrders API to get updated deductions with indent_status
    this.service.get(this.buildQueueUrl()).subscribe((response: any) => {
      const updatedList = this.unwrapWoPage(response).rows;
      const updatedWo = updatedList.find((w: any) => w.workorder_no === wo.workorder_no);
      if (updatedWo && updatedWo.Deductions) {
        // Update the work order in the list
        const index = this.approvalList.findIndex((w: any) => w.workorder_no === wo.workorder_no);
        if (index !== -1) {
          // Update deductions with latest indent_status from database
          this.approvalList[index].Deductions = updatedWo.Deductions;
          // Reprocess deductions to check indent_status
          this.processDeductions(this.approvalList[index]);
        }
      }
    }, (error) => {
      console.error('Error refreshing work order deductions:', error);
      // Fallback to full refresh
      this.getParkedWorkOrders();
    });
  }
  
  // Verify stock for a work order - calls real stock verification API
  verifyStock(wo: any) {
    if (!wo || !wo.workorder_no) {
      alert('Invalid work order');
      return;
    }

    this.verifyingStock[wo.workorder_no] = true;
    const verifyData = { workorder_no: wo.workorder_no };

    this.service.post(
      'bmr/line_booking.php?type=recheckLineApprovalStock',
      JSON.stringify(verifyData)
    ).subscribe((response: any) => {
      this.verifyingStock[wo.workorder_no] = false;

      if (response.status === 'success') {
        wo.Deductions = response.deductions || [];
        this.processDeductions(wo);
        const msg = response.message
          || (wo.hasShortage
            ? `Stock short on own material code for work order ${wo.workorder_no}.`
            : `Own-code stock is available for work order ${wo.workorder_no}.`);
        if (wo.indentRaised) {
          alert(msg + ' Indent is already raised.');
        } else {
          alert(msg);
        }
      } else {
        alert('Error verifying stock: ' + (response.message || 'Unknown error'));
      }
    }, () => {
      this.verifyingStock[wo.workorder_no] = false;
      alert('Error verifying stock. Please try again.');
    });
  }
  
  // Check if stock is being verified for a work order
  isVerifyingStock(wo: any): boolean {
    return this.verifyingStock[wo.workorder_no] || false;
  }
  
  // Check if stock has been verified
  isStockVerified(wo: any): boolean {
    return wo.stockVerified === true;
  }
  
  getOrderQty(wo: any): string {
    const candidates = [wo?.order_qty, wo?.orderQty, wo?.planQty, wo?.plan_qty, wo?.batch_size];
    for (const c of candidates) {
      if (c !== '' && c != null) {
        return String(c);
      }
    }
    return '—';
  }

  getOrderUom(wo: any): string {
    const candidates = [wo?.orderUnit, wo?.uom, wo?.planUnit, wo?.plan_unit];
    for (const c of candidates) {
      const s = (c ?? '').toString().trim();
      if (s) {
        return s;
      }
    }
    return 'Nos';
  }

  // View selected lines for a work order
  View2(wo: any) {
    this.isView2 = true;
    this.selectedWo = wo;
  }
  
  // View shortage materials
  ViewShortages(wo: any) {
    this.isShortages = true;
    this.selectedWo = wo;
    this.shortageMaterials = wo.shortageMaterials || [];
  }
  
  // Raise Indent for shortage materials - Updated to match shortages component structure
  raiseIndent(wo: any) {
    if (!wo.shortageMaterials || wo.shortageMaterials.length === 0) {
      alert('No shortage materials to raise indent');
      return;
    }
    
    if (!wo.Deductions || wo.Deductions.length === 0) {
      alert('Deductions not found. Please verify stock first.');
      return;
    }
    
    // Check if indent is already raised
    if (wo.indentRaised) {
      alert('Indent is already raised for this work order. Cannot raise indent again.');
      return;
    }
    
    // Check if any shortage material already has indent raised
    const materialsWithIndent = wo.shortageMaterials.filter((mat: any) => mat.indent_raised);
    if (materialsWithIndent.length > 0) {
      alert(`Indent is already raised for ${materialsWithIndent.length} material(s). Cannot raise indent again.`);
      return;
    }
    
    if (confirm(`Raise indent for ${wo.shortageMaterials.length} shortage material(s)?`)) {
      // Filter out materials that already have indent raised
      const materialsWithoutIndent = wo.shortageMaterials.filter((mat: any) => !mat.indent_raised);
      
      if (materialsWithoutIndent.length === 0) {
        alert('All shortage materials already have indent raised. Cannot raise indent again.');
        return;
      }
      
      // Group materials by material_code to consolidate shortages
      const materialMap: any = {};
      
      materialsWithoutIndent.forEach((mat: any) => {
        const matCode = mat.material_code;
        if (!materialMap[matCode]) {
          materialMap[matCode] = {
            material_code: matCode,
            material_name: mat.material_name || '',
            mat_type: mat.mat_type || 'RM',
            material_subtype: mat.mat_type === 'PM' ? 'Packing Material' : 'Raw Material',
            unit: mat.unit || '',
            total_rm_shortage: 0,
            wos: []
          };
        }

        const deduction = wo.Deductions.find((ded: any) => ded.material_code === matCode);
        if (deduction) {
          const rmShortage = Number(mat.shortage || 0);
          materialMap[matCode].total_rm_shortage += rmShortage;

          const woData = {
            workorder_no: wo.workorder_no,
            workorder_ID: deduction.id || '',
            material_code: matCode,
            material_name: mat.material_name || '',
            Matunit: mat.unit || '',
            rm_shortage: rmShortage.toString(),
            mc_shortage: '0',
            used_from_RM: Number(mat.deducted_from_RM || 0).toString(),
            used_from_MC: '0',
            required_for: wo.workorder_no,
            client_code: wo.client_code || '',
            client_name: wo.client_name || '',
            order_no: wo.order_no || '',
            product_code: wo.product_code || '',
            material_type: mat.mat_type || 'RM',
            material_subtype: mat.mat_type === 'PM' ? 'Packing Material' : 'Raw Material'
          };

          materialMap[matCode].wos.push(woData);
          materialMap[matCode].category = 'Client';
          materialMap[matCode].indent_type = 'Client Code';
          materialMap[matCode].client_code = wo.client_code || '';
          materialMap[matCode].client_name = wo.client_name || '';
        }
      });
      
      // Convert materialMap to array and process each material group
      const materialsArray = Object.values(materialMap);
      let successCount = 0;
      let errorCount = 0;
      
      // Process each material group sequentially
      materialsArray.forEach((materialData: any, index: number) => {
        const indentData = {
          category: 'Client',
          indent_type: 'Client Code',
          material_code: materialData.material_code,
          material_name: materialData.material_name,
          Matunit: materialData.unit,
          material_type: materialData.mat_type,
          material_subtype: materialData.material_subtype,
          total_rm_shortage: materialData.total_rm_shortage,
          total_mc_shortage: 0,
          client_code: materialData.client_code || '',
          client_name: materialData.client_name || '',
          wos: materialData.wos,
          required_for: materialData.wos.map((w: any) => ({
            reqQty: w.rm_shortage,
            Matunit: w.Matunit,
            client_name: w.client_name,
            order_no: w.order_no,
            product_code: w.product_code,
            work_order_no: w.workorder_no,
            Client_code_Indent: w.rm_shortage,
            material_code: w.material_code
          })),
          other_purpose: 'Raised from Line Approval - raiseIndentForShortage',
          indent_source: 'lineapproval'
        };
        
        // Send indent for this material group
        this.service.post(
          `purchase/indent.php?type=savePlanningIndentStore`,
          JSON.stringify(indentData)
        ).subscribe((response: any) => {
          if (response.status === 'success') {
            successCount++;
            if (successCount + errorCount === materialsArray.length) {
              alert(`Indent raised successfully for ${successCount} material(s)${errorCount > 0 ? `. ${errorCount} failed.` : ''}`);
              // Refresh the work order to get updated deductions with indent_status
              this.refreshWorkOrderDeductions(wo);
            }
          } else {
            // Check if it's a duplicate indent error
            if (response.error_code === 'DUPLICATE_INDENT' || response.message?.includes('already raised')) {
              errorCount++;
              alert(`Error: ${response.message}\n\nDetails: ${response.details || 'Indent is already raised for one or more materials.'}\n\nCannot raise duplicate indent.`);
              // Refresh to get latest indent status
              this.refreshWorkOrderDeductions(wo);
              if (successCount + errorCount === materialsArray.length) {
                this.getParkedWorkOrders();
              }
            } else {
              errorCount++;
              if (successCount + errorCount === materialsArray.length) {
                alert(`Indent raised for ${successCount} material(s). ${errorCount} failed: ${response.message || 'Unknown error'}`);
                // Refresh the work order to get updated deductions with indent_status
                if (successCount > 0) {
                  this.refreshWorkOrderDeductions(wo);
                } else {
                  this.getParkedWorkOrders();
                }
              }
            }
          }
        }, (error) => {
          console.error('Error raising indent:', error);
          errorCount++;
          if (successCount + errorCount === materialsArray.length) {
            alert(`Indent raised for ${successCount} material(s). ${errorCount} failed.`);
            // Refresh the work order to get updated deductions with indent_status
            if (successCount > 0) {
              this.refreshWorkOrderDeductions(wo);
            } else {
              this.getParkedWorkOrders();
            }
          }
        });
      });
    }
  }
  
  // Approve work order
  approveWorkOrder(wo: any) {
    if (confirm('Approve this line booking? The work order will go to Production → Batch Planning next.')) {
      this.service.post(
        `bmr/line_booking.php?type=approveWorkOrder`,
        JSON.stringify({ workorder_id: wo.id, workorder_no: wo.workorder_no })
      ).subscribe((response: any) => {
        if (response.status === 'success') {
          alert(response.message || 'Line approved. Next: Production → Batch Planning.');
          this.getParkedWorkOrders();
        } else {
          alert('Error approving work order: ' + (response.message || response.status));
        }
      });
    }
  }
  
  // Reject work order
  rejectWorkOrder(wo: any) {
    if (confirm('Are you sure you want to reject this work order?')) {
      this.service.post(
        `bmr/line_booking.php?type=rejectWorkOrder`,
        JSON.stringify({ workorder_id: wo.id, workorder_no: wo.workorder_no })
      ).subscribe((response: any) => {
        if (response.status === 'success') {
          alert('Work order rejected successfully!');
          this.getParkedWorkOrders();
        } else {
          alert('Error rejecting work order: ' + (response.message || response.status));
        }
      });
    }
  }
  
  // Helper method to display stages
  getStagesDisplay(stages: any[]): string {
    if (!stages || stages.length === 0) return '';
    return stages.map((s: any) => `${s.dosage_form || ''} - ${s.stage || ''}`).filter((s: string) => s.trim() !== '-').join(', ');
  }

  // Get log data for approved work orders - returns work orders with Booked status
  getLogData() {
    this.logLoading = true;
    this.service.get(this.buildLogUrl()).subscribe({
      next: (response: any) => {
        const page = this.unwrapWoPage(response);
        this.logData = page.rows;
        this.logTotalRecords = page.total;
        this.logData.forEach((wo: any) => {
          this.initializeStockStatus(wo);
        });
        this.logDataBackup = this.logData;
        if (this.logData.length === 0 && this.logCurrentPage > 1 && this.logTotalRecords > 0) {
          this.logCurrentPage -= 1;
          this.getLogData();
          return;
        }
        this.logLoading = false;
      },
      error: (err) => {
        this.logLoading = false;
        console.error('Error fetching log data:', err);
        alert('Error loading log data');
        this.logData = [];
        this.logDataBackup = [];
        this.logTotalRecords = 0;
      }
    });
  }

  applyLogFilter() {
    if (this.logSearchDebounceTimer) {
      clearTimeout(this.logSearchDebounceTimer);
    }
    this.logSearchDebounceTimer = setTimeout(() => {
      this.logCurrentPage = 1;
      this.getLogData();
    }, 350);
  }

  onLogPageChange(page: number): void {
    if (!page || page === this.logCurrentPage) {
      return;
    }
    this.logCurrentPage = page;
    this.getLogData();
  }

  onLogPageSizeChange(size: number): void {
    if (!size || size === this.logPageSize) {
      return;
    }
    this.logPageSize = size;
    this.logCurrentPage = 1;
    this.getLogData();
  }

  // Print log data
  printLog() {
    const printContent = document.getElementById('logTable');
    if (!printContent) {
      alert('Log table not found');
      return;
    }

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
      alert('Please allow popups to print');
      return;
    }

    printWindow.document.write(`
      <html>
        <head>
          <title>Approved Work Orders Log</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background-color: rgb(14, 67, 112); color: white; }
            tr:nth-child(even) { background-color: #f2f2f2; }
            h2 { text-align: center; margin-bottom: 20px; }
            .print-date { text-align: right; margin-bottom: 10px; }
          </style>
        </head>
        <body>
          <h2>Approved Work Orders Log</h2>
          <div class="print-date">Printed on: ${new Date().toLocaleString()}</div>
          ${printContent.innerHTML}
        </body>
      </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
      printWindow.print();
      printWindow.close();
    }, 250);
  }
}

