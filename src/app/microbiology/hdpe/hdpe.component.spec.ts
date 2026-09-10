import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HDPEComponent } from './hdpe.component';

describe('HDPEComponent', () => {
  let component: HDPEComponent;
  let fixture: ComponentFixture<HDPEComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ HDPEComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(HDPEComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
