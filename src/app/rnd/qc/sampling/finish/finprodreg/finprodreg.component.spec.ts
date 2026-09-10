import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FinprodregComponent } from './finprodreg.component';

describe('FinprodregComponent', () => {
  let component: FinprodregComponent;
  let fixture: ComponentFixture<FinprodregComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FinprodregComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FinprodregComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
