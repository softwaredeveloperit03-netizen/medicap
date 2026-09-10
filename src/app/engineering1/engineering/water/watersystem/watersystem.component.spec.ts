import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WatersystemComponent } from './watersystem.component';

describe('WatersystemComponent', () => {
  let component: WatersystemComponent;
  let fixture: ComponentFixture<WatersystemComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WatersystemComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WatersystemComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
