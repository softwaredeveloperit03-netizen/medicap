import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ProdapprovalComponent } from './prodapproval.component';

describe('ProdapprovalComponent', () => {
  let component: ProdapprovalComponent;
  let fixture: ComponentFixture<ProdapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ProdapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ProdapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
