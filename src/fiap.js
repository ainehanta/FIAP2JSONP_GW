
import moment from 'moment';

export default class FiapClient {
  constructor(fiap2json_url, fiap_url) {
    this.fiap2json_url = fiap2json_url;
    this.fiap_url       = fiap_url;
  }

  fetchLatest(point_id, callback) {
    let point_ids = this._sanitize_to_array(point_id);

    this.query(
      point_ids.map((point_id_) => {
        return {
          id : point_id_,
          attrName : 'time',
          select : 'maximum'
        };
      }),
      callback
    );
  }

  fetchLatestOneHour(point_id, callback) {
    let gteq = moment().subtract(1, 'hours').format();
    let lteq = moment().format();

    let point_ids = this._sanitize_to_array(point_id)

    return this.query(
      point_ids.map((point_id_) => {
        return {
          'id' : point_id_,
          'attrName' : 'time',
          'gteq' : gteq,
          'lteq' : lteq,
        };
      }),
      callback
    );
  }

  fetchLatestOneDay(point_id, callback) {
    let gteq = moment().subtract(1, 'days').format();
    let lteq = moment().format();

    let point_ids = this._sanitize_to_array(point_id)

    return this.query(
      point_ids.map((point_id_) => {
        return {
          'id' : point_id_,
          'attrName' : 'time',
          'gteq' : gteq,
          'lteq' : lteq,
        };
      }),
      callback
    );
  }

  fetchByTime(point_id, from, to, callback) {
    let gteq = from.format();
    let lteq = to.format();

    let point_ids = this._sanitize_to_array(point_id)

    return this.query(
      point_ids.map((point_id_) => {
        return {
          'id' : point_id_,
          'attrName' : 'time',
          'gteq' : gteq,
          'lteq' : lteq,
        };
      }),
      callback
    );
  }

  fetchByJustTime(point_id, eq, callback) {
    let eqs = this._sanitize_to_array(eq);
    eqs.map((eq_) => eq_.format());

    let point_ids = this._sanitize_to_array(point_id)

    let keys = [];
    eqs.forEach(eqs, (eq_) => {
      point_ids.forEach((point_id_) => {
        keys.push({
          'id' : point_id_,
          'attrName' : 'time',
          'eq' : eq_,
        });
      });
    });
    return this.query(
      keys,
      callback
    );
  }

  query(keys, callback) {
    let requestBody = { fiap_url: this.fiap_url, keys: keys };
    let requestHeader = new Headers({
        'Content-Type': 'application/json'
    });

    fetch(this.fiap2json_url, {
      method: 'POST', mode: 'cors', body: JSON.stringify(requestBody),
      headers: requestHeader
    })
    .then(response => {
      if(!response.ok) {
        return response.json().then((json) => {
          throw Error([json, response.status]);
        })
      } else {
        return response;
      }
    })
    .then(response => response.json().then((json) => {
      callback(json, response.status);
    }))
    .catch(err => callback(...err));
  }

  _sanitize_to_array(object) {
    return object instanceof Array ? object : [object];
  }
}
