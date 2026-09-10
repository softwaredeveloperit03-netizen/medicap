import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BfrapprovalComponent } from './bfrapproval.component';

describe('BfrapprovalComponent', () => {
  let component: BfrapprovalComponent;
  let fixture: ComponentFixture<BfrapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BfrapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(BfrapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
