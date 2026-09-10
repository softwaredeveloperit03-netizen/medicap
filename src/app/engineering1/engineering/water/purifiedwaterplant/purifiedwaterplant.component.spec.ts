import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PurifiedwaterplantComponent } from './purifiedwaterplant.component';

describe('PurifiedwaterplantComponent', () => {
  let component: PurifiedwaterplantComponent;
  let fixture: ComponentFixture<PurifiedwaterplantComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PurifiedwaterplantComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PurifiedwaterplantComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
