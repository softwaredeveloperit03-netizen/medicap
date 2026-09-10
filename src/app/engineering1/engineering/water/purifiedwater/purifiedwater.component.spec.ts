import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PurifiedwaterComponent } from './purifiedwater.component';

describe('PurifiedwaterComponent', () => {
  let component: PurifiedwaterComponent;
  let fixture: ComponentFixture<PurifiedwaterComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PurifiedwaterComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PurifiedwaterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
