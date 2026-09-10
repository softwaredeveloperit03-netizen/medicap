import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PromproductionComponent } from './promproduction.component';

describe('PromproductionComponent', () => {
  let component: PromproductionComponent;
  let fixture: ComponentFixture<PromproductionComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PromproductionComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PromproductionComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
