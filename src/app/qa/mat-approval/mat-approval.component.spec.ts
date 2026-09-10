import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MatApprovalComponent } from './mat-approval.component';

describe('MatApprovalComponent', () => {
  let component: MatApprovalComponent;
  let fixture: ComponentFixture<MatApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MatApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MatApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
