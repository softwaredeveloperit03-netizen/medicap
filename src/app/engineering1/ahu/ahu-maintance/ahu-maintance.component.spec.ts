import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AhuMaintanceComponent } from './ahu-maintance.component';

describe('AhuMaintanceComponent', () => {
  let component: AhuMaintanceComponent;
  let fixture: ComponentFixture<AhuMaintanceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ AhuMaintanceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AhuMaintanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
