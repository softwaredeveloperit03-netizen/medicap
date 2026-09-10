import { ComponentFixture, TestBed } from '@angular/core/testing';

import { IpqcComponent } from './ipqc.component';

describe('IpqcComponent', () => {
  let component: IpqcComponent;
  let fixture: ComponentFixture<IpqcComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ IpqcComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(IpqcComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
