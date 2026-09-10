import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AmmendementComponent } from './ammendement.component';

describe('AmmendementComponent', () => {
  let component: AmmendementComponent;
  let fixture: ComponentFixture<AmmendementComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AmmendementComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AmmendementComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
