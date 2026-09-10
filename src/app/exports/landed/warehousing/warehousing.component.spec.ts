import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WarehousingComponent } from './warehousing.component';

describe('WarehousingComponent', () => {
  let component: WarehousingComponent;
  let fixture: ComponentFixture<WarehousingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WarehousingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WarehousingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
