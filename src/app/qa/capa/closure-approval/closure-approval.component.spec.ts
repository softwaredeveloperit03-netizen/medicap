import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ClosureApprovalComponent } from './closure-approval.component';

describe('ClosureApprovalComponent', () => {
  let component: ClosureApprovalComponent;
  let fixture: ComponentFixture<ClosureApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ClosureApprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ClosureApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
