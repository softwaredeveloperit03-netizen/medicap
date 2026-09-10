import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FactoryorderComponent } from './factoryorder.component';

describe('FactoryorderComponent', () => {
  let component: FactoryorderComponent;
  let fixture: ComponentFixture<FactoryorderComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FactoryorderComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(FactoryorderComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
