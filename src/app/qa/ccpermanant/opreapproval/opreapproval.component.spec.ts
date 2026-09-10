import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OpreapprovalComponent } from './opreapproval.component';

describe('OpreapprovalComponent', () => {
  let component: OpreapprovalComponent;
  let fixture: ComponentFixture<OpreapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OpreapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OpreapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
